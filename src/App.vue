<template>
	<NcAppContent>
		<div class="content-wrapper">
			<div v-if="!selectedMap" class="mindmap-list">
				<div class="header">
					<h2>Mind Maps</h2>
					<NcButton @click="createNewMap" type="primary">
						<template #icon>
							<Plus :size="20" />
						</template>
						New MindMap
					</NcButton>
				</div>
				
				<div v-if="loading" class="loading">
					<NcLoadingIcon :size="32" />
					<span>Loading mind maps...</span>
				</div>
				
				<div v-else-if="mindMaps.length === 0" class="empty-state">
					<p>You don't have any mind maps yet.</p>
					<NcButton @click="createNewMap" type="primary">Create your first mind map</NcButton>
				</div>
				
				<div v-else class="map-grid">
					<div 
						v-for="map in mindMaps" 
						:key="map.id" 
						class="map-card"
						@click="openMindMap(map.name)"
					>
						<div class="map-card-content">
							<h3>{{ map.name }}</h3>
							<p class="meta">Last updated: {{ formatDate(map.updatedAt) }}</p>
						</div>
						<div class="map-card-actions">
							<NcButton @click.stop="deleteMindMap(map.name)" type="tertiary" title="Delete">
								<template #icon>
									<Delete :size="20" />
								</template>
							</NcButton>
						</div>
					</div>
				</div>
			</div>
			
			<div v-else class="mind-map-view">
				<div class="mind-map-header">
					<NcButton @click="goBack" type="tertiary">
						<template #icon>
							<ArrowLeft :size="20" />
						</template>
						Back to list
					</NcButton>
					<h2>{{ selectedMap }}</h2>
				</div>
				
				<MindMapCanvas :mind-map-id="selectedMap" class="mind-map-canvas" />
			</div>
		</div>
	</NcAppContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import { Plus, Delete, ArrowLeft } from '@nextcloud/vue-richtext/icons'
import { ref, onMounted } from 'vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'
import MindMapCanvas from './components/MindMapCanvas.vue'

export default {
	name: 'App',
	components: {
		NcAppContent,
		NcButton,
		NcLoadingIcon,
		Plus,
		Delete,
		ArrowLeft,
		MindMapCanvas,
	},
	setup() {
		const mindMaps = ref([])
		const loading = ref(true)
		const selectedMap = ref(null)

		// Load all mind maps
		const loadMindMaps = async () => {
			try {
				loading.value = true
				const response = await axios.get(generateUrl('/apps/mindmap/api/mindmaps'))
				mindMaps.value = response.data
			} catch (error) {
				console.error('Error loading mind maps:', error)
				showError('Error loading mind maps')
			} finally {
				loading.value = false
			}
		}

		// Create a new mind map
		const createNewMap = async () => {
			const name = prompt('Enter a name for your new mind map:', 'My Mind Map')
			if (!name) return

			try {
				// Create basic template content
				const content = JSON.stringify({
					nodes: [
						{
							id: 'node-1',
							type: 'default',
							position: { x: 0, y: 0 },
							data: { content: `<h3>${name}</h3><p>Start your mindmap here...</p>` },
						},
					],
					edges: []
				})

				await axios.post(generateUrl(`/apps/mindmap/api/mindmaps/${name}`), { content })
				showSuccess('Mind map created')
				await loadMindMaps()
				openMindMap(name)
			} catch (error) {
				console.error('Error creating mind map:', error)
				showError('Error creating mind map')
			}
		}

		// Delete a mind map
		const deleteMindMap = async (name) => {
			if (!confirm(`Are you sure you want to delete "${name}"?`)) return

			try {
				await axios.delete(generateUrl(`/apps/mindmap/api/mindmaps/${name}`))
				showSuccess('Mind map deleted')
				await loadMindMaps()
			} catch (error) {
				console.error('Error deleting mind map:', error)
				showError('Error deleting mind map')
			}
		}

		// Open a mind map
		const openMindMap = (name) => {
			selectedMap.value = name
		}

		// Go back to the list view
		const goBack = () => {
			selectedMap.value = null
			loadMindMaps()
		}

		// Format date
		const formatDate = (dateString) => {
			const date = new Date(dateString)
			return date.toLocaleString()
		}

		onMounted(() => {
			loadMindMaps()
		})

		return {
			mindMaps,
			loading,
			selectedMap,
			createNewMap,
			deleteMindMap,
			openMindMap,
			goBack,
			formatDate,
		}
	}
}
</script>

<style scoped lang="scss">
.content-wrapper {
	display: flex;
	flex-direction: column;
	width: 100%;
	height: calc(100vh - 50px);
	padding: 16px;
	box-sizing: border-box;
}

.mindmap-list {
	width: 100%;
	height: 100%;
}

.header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16px;
}

.loading, .empty-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 200px;
	gap: 16px;
}

.map-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
	gap: 16px;
}

.map-card {
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: 16px;
	background-color: var(--color-background-hover);
	cursor: pointer;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	transition: box-shadow 0.2s;
	
	&:hover {
		box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
	}
}

.map-card-content {
	h3 {
		margin-top: 0;
		margin-bottom: 4px;
	}
	
	.meta {
		color: var(--color-text-maxcontrast);
		font-size: 0.9rem;
		margin: 0;
	}
}

.map-card-actions {
	display: flex;
	justify-content: flex-end;
	margin-top: 16px;
}

.mind-map-view {
	display: flex;
	flex-direction: column;
	width: 100%;
	height: 100%;
}

.mind-map-header {
	display: flex;
	align-items: center;
	margin-bottom: 16px;
	gap: 16px;
}

.mind-map-canvas {
	flex: 1;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	overflow: hidden;
}
</style>
