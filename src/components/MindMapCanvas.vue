<template>
  <div class="mindmap-canvas-container" ref="container">
    <div class="vue-flow-wrapper">
      <VueFlow
        v-model="elements"
        :default-zoom="1"
        :min-zoom="0.2"
        :max-zoom="4"
        :snapToGrid="true"
        :elevateEdgesOnSelect="true"
        @elementClick="onElementClick"
        @connect="onConnect"
        @paneReady="onPaneReady"
        @moveEnd="onMoveEnd"
      >
        <template #node-default="nodeProps">
          <NodeContent 
            :id="nodeProps.id"
            :data="nodeProps.data"
            :selected="nodeProps.selected"
            @update="updateNodeContent"
          />
        </template>

        <Background pattern-color="#aaa" gap="20" />
        <Controls />
        <MiniMap />

        <Panel position="top-right">
          <MindMapToolbar @add-node="addNode" @add-mindmap="addMindMap" @save-all="saveMindMap" />
        </Panel>

        <!-- User cursors -->
        <div v-for="(cursor, key) in remoteCursors" :key="key" 
             :style="{ position: 'absolute', left: cursor.x + 'px', top: cursor.y + 'px', pointerEvents: 'none', zIndex: 1000 }">
          <div class="remote-cursor" :style="{ backgroundColor: cursor.color }">
            <div class="remote-cursor-name">{{ cursor.name }}</div>
          </div>
        </div>
      </VueFlow>
    </div>
  </div>
</template>

<script>
import VueFlow, { 
  Background,
  Controls,
  MiniMap,
  Panel,
  isNode,
  isEdge
} from 'vue-flow'
import 'vue-flow/dist/style.css'
import 'vue-flow/dist/theme-default.css'
import { nextTick, onMounted, onUnmounted, ref, reactive, computed } from 'vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'
import * as Y from 'yjs'
import { WebsocketProvider } from 'y-websocket'

import NodeContent from './NodeContent.vue'
import MindMapToolbar from './MindMapToolbar.vue'

export default {
  name: 'MindMapCanvas',
  components: {
    VueFlow,
    Background,
    Controls,
    MiniMap,
    Panel,
    NodeContent,
    MindMapToolbar
  },
  props: {
    mindMapId: {
      type: String,
      default: 'default'
    }
  },
  setup(props) {
    // Reference to the container element
    const container = ref(null)
    const instance = ref(null)

    // Local state
    const elements = ref([])
    const remoteCursors = ref({})
    const lockedNodes = ref({})
    const ydoc = ref(null)
    const provider = ref(null)
    const wsConnected = ref(false)
    const elementsMap = ref(null)
    const mousePosition = reactive({ x: 0, y: 0 })
    const debounceTimeout = ref(null)
    
    // Generate a random color for the user's cursor
    const userColor = '#' + Math.floor(Math.random()*16777215).toString(16)
    let userId = null
    let userName = null

    // Computed properties for nodes and edges
    const nodes = computed(() => {
      return elements.value.filter(el => isNode(el))
    })

    const edges = computed(() => {
      return elements.value.filter(el => isEdge(el))
    })

    // Load the mindmap data
    const loadMindMap = async () => {
      try {
        const response = await axios.get(generateUrl(`/apps/mindmap/api/mindmaps/${props.mindMapId}`))
        if (response.data) {
          const data = JSON.parse(response.data.content || '{}')
          
          if (data.nodes && Array.isArray(data.nodes)) {
            // Convert the nodes to the format expected by vue-flow
            const flowElements = [
              ...data.nodes.map(node => ({
                id: node.id,
                type: 'default',
                position: node.position,
                data: node.data || { content: '<p>Node content</p>' },
              })),
              ...(data.edges || []).map(edge => ({
                id: edge.id,
                source: edge.source,
                target: edge.target,
                type: 'default',
                animated: false,
                arrowHeadType: 'arrowclosed',
              }))
            ]
            
            elements.value = flowElements
          } else {
            // Create a default mindmap with one node
            elements.value = [
              {
                id: 'node-1',
                type: 'default',
                position: { x: 0, y: 0 },
                data: { content: `<h3>${props.mindMapId}</h3><p>Start your mindmap here...</p>` },
              },
            ]
          }
        } else {
          // Create a default mindmap with one node
          elements.value = [
            {
              id: 'node-1',
              type: 'default',
              position: { x: 0, y: 0 },
              data: { content: `<h3>${props.mindMapId}</h3><p>Start your mindmap here...</p>` },
            },
          ]
        }
      } catch (error) {
        console.error('Error loading mindmap:', error)
        showError('Error loading mindmap')
        
        // Create a default mindmap if there's an error
        elements.value = [
          {
            id: 'node-1',
            type: 'default',
            position: { x: 0, y: 0 },
            data: { content: `<h3>${props.mindMapId}</h3><p>Start your mindmap here...</p>` },
          },
        ]
      }
    }

    // Save the mindmap
    const saveMindMap = async () => {
      try {
        if (debounceTimeout.value) {
          clearTimeout(debounceTimeout.value)
        }

        debounceTimeout.value = setTimeout(async () => {
          // Convert vue-flow elements to the format expected by our API
          const data = {
            nodes: nodes.value.map(node => ({
              id: node.id,
              position: node.position,
              data: node.data,
              type: node.type
            })),
            edges: edges.value.map(edge => ({
              id: edge.id,
              source: edge.source,
              target: edge.target,
              type: edge.type
            }))
          }
          
          const content = JSON.stringify(data)
          await axios.post(
            generateUrl(`/apps/mindmap/api/mindmaps/${props.mindMapId}`),
            { content }
          )
          showSuccess('MindMap saved')
        }, 1000)
      } catch (error) {
        console.error('Error saving mindmap:', error)
        showError('Error saving mindmap')
      }
    }

    // Setup WebSocket connection for real-time collaboration
    const setupCollaboration = async () => {
      try {
        const response = await axios.get(
          generateUrl(`/apps/mindmap/api/mindmaps/${props.mindMapId}/socket`)
        )
        
        if (response.data && !response.data.error) {
          const { wsUrl, token, userId: currentUserId, displayName } = response.data

          userId = currentUserId
          userName = displayName

          // Initialize the shared document
          ydoc.value = new Y.Doc()
          
          // Create shared data structures
          elementsMap.value = ydoc.value.getMap('elements')
          const cursors = ydoc.value.getMap('cursors')

          // Connect to WebSocket server
          provider.value = new WebsocketProvider(wsUrl, props.mindMapId, ydoc.value, {
            params: { token },
          })

          provider.value.on('status', (event) => {
            wsConnected.value = event.status === 'connected'
          })

          // Load initial data into shared structures
          elements.value.forEach(element => {
            elementsMap.value.set(element.id, element)
          })

          // Watch for changes in the shared document
          elementsMap.value.observe(event => {
            event.changes.keys.forEach((change, key) => {
              if (change.action === 'add' || change.action === 'update') {
                const element = elementsMap.value.get(key)
                const existingIndex = elements.value.findIndex(e => e.id === key)
                
                if (existingIndex >= 0) {
                  elements.value[existingIndex] = element
                } else {
                  elements.value.push(element)
                }
              } else if (change.action === 'delete') {
                elements.value = elements.value.filter(e => e.id !== key)
              }
            })
          })

          // Remote cursors
          cursors.observe(event => {
            event.changes.keys.forEach((change, key) => {
              if (change.action === 'add' || change.action === 'update') {
                const cursor = cursors.get(key)
                if (key !== userId) {
                  remoteCursors.value[key] = cursor
                }
              } else if (change.action === 'delete') {
                delete remoteCursors.value[key]
              }
            })
            nextTick(() => {})
          })

          // Track mouse movement
          if (container.value) {
            container.value.addEventListener('mousemove', trackMouse)
          }
        } else {
          showError(response.data.error || 'Failed to connect to collaboration server')
        }
      } catch (error) {
        console.error('Error setting up collaboration:', error)
        showError('Error setting up collaboration')
      }
    }

    // Update cursor position
    const trackMouse = (event) => {
      if (!provider.value || !wsConnected.value) return

      const rect = container.value.getBoundingClientRect()
      const x = event.clientX - rect.left
      const y = event.clientY - rect.top

      mousePosition.x = x
      mousePosition.y = y

      if (debounceTimeout.value) {
        clearTimeout(debounceTimeout.value)
      }

      debounceTimeout.value = setTimeout(() => {
        const cursors = ydoc.value.getMap('cursors')
        cursors.set(userId, {
          x,
          y,
          color: userColor,
          name: userName,
          userId
        })
      }, 50)
    }

    // Add a new node
    const addNode = () => {
      const id = `node-${Date.now()}`
      const position = { 
        x: mousePosition.x - 75, 
        y: mousePosition.y - 30 
      }
      
      const newNode = {
        id,
        type: 'default',
        position,
        data: { content: '<p>New node</p>' },
      }
      
      if (elementsMap.value) {
        elementsMap.value.set(id, newNode)
      } else {
        elements.value.push(newNode)
      }
      
      saveMindMap()
    }
    
    // Add a new mindmap
    const addMindMap = () => {
      const id = `mindmap-${Date.now()}`
      const position = { 
        x: mousePosition.x - 75, 
        y: mousePosition.y - 30 
      }
      
      const newNode = {
        id,
        type: 'default',
        position,
        data: { 
          content: '<h3>New Mindmap</h3><p>Start here...</p>',
          isMindMap: true
        },
      }
      
      if (elementsMap.value) {
        elementsMap.value.set(id, newNode)
      } else {
        elements.value.push(newNode)
      }
      
      saveMindMap()
    }

    // Handle element click for locking
    const onElementClick = (event, element) => {
      if (!isNode(element)) return
      
      // Toggle lock for editing
      if (!lockedNodes.value[element.id]) {
        lockedNodes.value[element.id] = userId
        
        // Share lock status
        if (ydoc.value) {
          const locks = ydoc.value.getMap('locks')
          locks.set(element.id, userId)
        }
        
        console.log(`Node ${element.id} locked for editing by ${userId}`)
      }
    }

    // Update node content
    const updateNodeContent = ({ id, content }) => {
      // Find the node and update its content
      const nodeIndex = elements.value.findIndex(el => el.id === id && isNode(el))
      if (nodeIndex >= 0) {
        const node = elements.value[nodeIndex]
        node.data = { ...node.data, content }
        
        if (elementsMap.value) {
          elementsMap.value.set(id, node)
        }
        
        saveMindMap()
      }
    }

    // Create a connection between nodes
    const onConnect = (params) => {
      const edge = {
        id: `edge-${Date.now()}`,
        source: params.source,
        target: params.target,
        type: 'default',
        animated: false,
        arrowHeadType: 'arrowclosed',
      }
      
      if (elementsMap.value) {
        elementsMap.value.set(edge.id, edge)
      } else {
        elements.value.push(edge)
      }
      
      saveMindMap()
    }

    // Store the Vue Flow instance
    const onPaneReady = (flowInstance) => {
      instance.value = flowInstance
    }

    // Save when panning or zooming ends
    const onMoveEnd = () => {
      saveMindMap()
    }

    // Lifecycle hooks
    onMounted(async () => {
      await loadMindMap()
      await setupCollaboration()
    })

    onUnmounted(() => {
      // Clean up
      if (provider.value) {
        provider.value.disconnect()
      }
      
      if (container.value) {
        container.value.removeEventListener('mousemove', trackMouse)
      }
      
      if (debounceTimeout.value) {
        clearTimeout(debounceTimeout.value)
      }
    })

    return {
      container,
      elements,
      onElementClick,
      onConnect,
      onPaneReady,
      onMoveEnd,
      remoteCursors,
      addNode,
      addMindMap,
      updateNodeContent,
      saveMindMap
    }
  }
}
</script>

<style scoped>
.mindmap-canvas-container {
  width: 100%;
  height: 100%;
  position: relative;
}

.vue-flow-wrapper {
  width: 100%;
  height: 100%;
}

.remote-cursor {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  position: relative;
}

.remote-cursor-name {
  position: absolute;
  top: -25px;
  left: -5px;
  background-color: #333;
  color: white;
  padding: 2px 5px;
  border-radius: 3px;
  font-size: 12px;
  white-space: nowrap;
}
</style> 