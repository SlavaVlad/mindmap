<template>
  <div :class="['node-content', { selected: selected }]">
    <div class="handle-container">
      <div class="handle handle-top" />
      <div class="handle handle-right" />
      <div class="handle handle-bottom" />
      <div class="handle handle-left" />
    </div>
    
    <div class="editor-container" @dblclick="startEditing" v-if="!isEditing">
      <!-- Display content -->
      <div class="content" v-html="data.content"></div>
    </div>
    
    <div v-else class="editor">
      <!-- Editor -->
      <editor-content :editor="editor" />
      
      <div class="toolbar">
        <button @click="toggleBold" :class="{ 'is-active': editor.isActive('bold') }">
          <span class="icon-bold"></span>
        </button>
        <button @click="toggleItalic" :class="{ 'is-active': editor.isActive('italic') }">
          <span class="icon-italic"></span>
        </button>
        <button @click="addImage">
          <span class="icon-picture"></span>
        </button>
        <button @click="saveContent">
          <span class="icon-checkmark"></span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { useEditor, EditorContent } from '@tiptap/vue-2'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import { ref, onMounted, onUnmounted } from 'vue'

export default {
  name: 'NodeContent',
  components: {
    EditorContent,
  },
  props: {
    id: {
      type: String,
      required: true
    },
    data: {
      type: Object,
      default: () => ({
        content: '<p>Node content</p>'
      })
    },
    selected: {
      type: Boolean,
      default: false
    },
    isConnectable: {
      type: Boolean,
      default: true
    },
    dragging: {
      type: Boolean,
      default: false
    }
  },
  setup(props, { emit }) {
    const isEditing = ref(false)
    const originalContent = ref('')
    
    // Initialize the editor
    const editor = useEditor({
      extensions: [
        StarterKit,
        Image.configure({
          inline: true,
          allowBase64: true,
          HTMLAttributes: {
            class: 'node-image',
          },
        }),
      ],
      content: props.data.content,
      editable: false,
      onUpdate: ({ editor }) => {
        // Handle content updates
      }
    })
    
    // Start editing
    const startEditing = () => {
      if (editor.value) {
        originalContent.value = props.data.content
        isEditing.value = true
        editor.value.setEditable(true)
      }
    }
    
    // Save the content
    const saveContent = () => {
      if (editor.value) {
        const content = editor.value.getHTML()
        emit('update', { id: props.id, content })
        isEditing.value = false
        editor.value.setEditable(false)
      }
    }
    
    // Cancel editing
    const cancelEditing = () => {
      if (editor.value) {
        editor.value.commands.setContent(originalContent.value)
        isEditing.value = false
        editor.value.setEditable(false)
      }
    }
    
    // Handle escape key
    const handleKeyDown = (e) => {
      if (e.key === 'Escape' && isEditing.value) {
        cancelEditing()
      } else if (e.key === 'Enter' && e.ctrlKey && isEditing.value) {
        saveContent()
      }
    }
    
    // Toggle bold
    const toggleBold = () => {
      editor.value?.chain().focus().toggleBold().run()
    }
    
    // Toggle italic
    const toggleItalic = () => {
      editor.value?.chain().focus().toggleItalic().run()
    }
    
    // Add image
    const addImage = () => {
      const input = document.createElement('input')
      input.type = 'file'
      input.accept = 'image/*'
      input.onchange = (event) => {
        const file = event.target.files[0]
        if (file) {
          const reader = new FileReader()
          reader.onload = (e) => {
            const src = e.target.result
            editor.value?.chain().focus().setImage({ src }).run()
          }
          reader.readAsDataURL(file)
        }
      }
      input.click()
    }
    
    // Handle paste events for images
    const handlePaste = (e) => {
      if (!isEditing.value || !editor.value) return
      
      const items = e.clipboardData.items
      for (const item of items) {
        if (item.type.indexOf('image') === 0) {
          e.preventDefault()
          const blob = item.getAsFile()
          const reader = new FileReader()
          reader.onload = (e) => {
            const src = e.target.result
            editor.value.chain().focus().setImage({ src }).run()
          }
          reader.readAsDataURL(blob)
          break
        }
      }
    }
    
    onMounted(() => {
      document.addEventListener('keydown', handleKeyDown)
      document.addEventListener('paste', handlePaste)
    })
    
    onUnmounted(() => {
      document.removeEventListener('keydown', handleKeyDown)
      document.removeEventListener('paste', handlePaste)
      editor.value?.destroy()
    })
    
    return {
      isEditing,
      editor,
      startEditing,
      saveContent,
      cancelEditing,
      toggleBold,
      toggleItalic,
      addImage,
    }
  }
}
</script>

<style scoped>
.node-content {
  min-width: 150px;
  min-height: 60px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background: white;
  padding: 10px;
  position: relative;
}

.node-content.selected {
  border-color: #1565c0;
  box-shadow: 0 0 0 2px rgba(21, 101, 192, 0.2);
}

.handle-container {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  pointer-events: none;
}

.handle {
  width: 10px;
  height: 10px;
  background: #1565c0;
  border-radius: 50%;
  position: absolute;
  pointer-events: all;
}

.handle-top {
  top: -5px;
  left: 50%;
  transform: translateX(-50%);
}

.handle-right {
  right: -5px;
  top: 50%;
  transform: translateY(-50%);
}

.handle-bottom {
  bottom: -5px;
  left: 50%;
  transform: translateX(-50%);
}

.handle-left {
  left: -5px;
  top: 50%;
  transform: translateY(-50%);
}

.editor-container {
  width: 100%;
  height: 100%;
}

.content {
  min-width: 100px;
  min-height: 40px;
}

.editor {
  width: 100%;
  min-height: 100px;
  position: relative;
}

.toolbar {
  margin-top: 10px;
  display: flex;
  gap: 5px;
}

.toolbar button {
  background: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 5px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toolbar button.is-active {
  background: #e3f2fd;
  border-color: #1565c0;
}

:deep(.node-image) {
  max-width: 100%;
  height: auto;
  margin: 5px 0;
}
</style> 