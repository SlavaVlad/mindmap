<template>
  <div class="toolbar">
    <button @click="addNode" title="Add Node">
      <span class="icon-add"></span> Node
    </button>
    <button @click="addMindMap" title="Add MindMap">
      <span class="icon-category-app"></span> MindMap
    </button>
    <button @click="saveAll" title="Save All">
      <span class="icon-save"></span>
    </button>
  </div>
</template>

<script>
export default {
  name: 'MindMapToolbar',
  emits: ['add-node', 'add-mindmap', 'save-all'],
  setup(props, { emit }) {
    const addNode = () => {
      emit('add-node')
    }
    
    const addMindMap = () => {
      emit('add-mindmap')
    }
    
    const saveAll = () => {
      emit('save-all')
    }
    
    return {
      addNode,
      addMindMap,
      saveAll
    }
  }
}
</script>

<style scoped>
.toolbar {
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 8px;
}

.toolbar button {
  display: flex;
  align-items: center;
  gap: 5px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 8px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.toolbar button:hover {
  background-color: #e3f2fd;
}
</style> 