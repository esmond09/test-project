<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowUpDown } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'CSV Uploads',
        href: '/uploads',
    },
];

interface FileUpload {
    id: number;
    original_filename: string;
    status: string;
    total_rows: number | null;
    processed_rows: number;
    error_message: string | null;
    created_at: string;
    updated_at: string;
}

type SortField = 'created_at' | 'original_filename';
type SortDirection = 'asc' | 'desc';

const uploads = ref<FileUpload[]>([]);
const isUploading = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const sortField = ref<SortField>('created_at');
const sortDirection = ref<SortDirection>('desc');
const pollingInterval = ref<number | null>(null);
const isDragging = ref(false);
const uploadQueue = ref<number>(0);

const hasProcessingUploads = computed(() => {
    return uploads.value.some(upload =>
        upload.status === 'pending' || upload.status === 'processing'
    );
});

const fetchUploads = async () => {
    try {
        const response = await axios.get('/api/uploads');
        uploads.value = response.data.data;
    } catch (error) {
        console.error('Error fetching uploads:', error);
    }
};

const startPolling = () => {
    if (pollingInterval.value) return;
    // Poll every 2 seconds only when there are processing uploads
    pollingInterval.value = window.setInterval(fetchUploads, 2000);
};

const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
};

const handleFileSelect = () => {
    fileInput.value?.click();
};

const uploadFile = async (file: File) => {
    // Validate file type
    if (!file.name.endsWith('.csv')) {
        alert(`${file.name} is not a CSV file. Skipping...`);
        return;
    }

    const formData = new FormData();
    formData.append('file', file);

    try {
        await axios.post('/api/uploads', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
    } catch (error: any) {
        console.error('Error uploading file:', error);
        alert(`Error uploading ${file.name}: ${error.response?.data?.message || 'Unknown error'}`);
    }
};

const handleFileUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = target.files;

    if (!files || files.length === 0) return;

    isUploading.value = true;
    uploadQueue.value = files.length;

    try {
        // Upload files sequentially
        for (let i = 0; i < files.length; i++) {
            await uploadFile(files[i]);
            uploadQueue.value--;
        }

        // Clear file input
        if (target) {
            target.value = '';
        }

        // Refresh the list after all uploads
        await fetchUploads();
    } finally {
        isUploading.value = false;
        uploadQueue.value = 0;
    }
};

const handleDragEnter = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
};

const handleDrop = async (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;

    const files = event.dataTransfer?.files;
    if (!files || files.length === 0) return;

    isUploading.value = true;
    uploadQueue.value = files.length;

    try {
        // Upload files sequentially
        for (let i = 0; i < files.length; i++) {
            await uploadFile(files[i]);
            uploadQueue.value--;
        }

        // Refresh the list after all uploads
        await fetchUploads();
    } finally {
        isUploading.value = false;
        uploadQueue.value = 0;
    }
};

const getStatusVariant = (status: string): "default" | "secondary" | "destructive" | "outline" => {
    switch (status) {
        case 'completed':
            return 'default';
        case 'processing':
            return 'secondary';
        case 'failed':
            return 'destructive';
        default:
            return 'outline';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};

const getProgress = (upload: FileUpload) => {
    if (!upload.total_rows) return 0;
    return Math.round((upload.processed_rows / upload.total_rows) * 100);
};

const toggleSort = (field: SortField) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'desc';
    }
};

const sortedUploads = computed(() => {
    const sorted = [...uploads.value];
    sorted.sort((a, b) => {
        let compareA: string | Date = a[sortField.value];
        let compareB: string | Date = b[sortField.value];

        if (sortField.value === 'created_at') {
            compareA = new Date(a.created_at);
            compareB = new Date(b.created_at);
        }

        if (compareA < compareB) return sortDirection.value === 'asc' ? -1 : 1;
        if (compareA > compareB) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
    return sorted;
});

// Watch for processing uploads and start/stop polling accordingly
watch(hasProcessingUploads, (hasProcessing) => {
    if (hasProcessing) {
        startPolling();
    } else {
        stopPolling();
    }
});

// Fetch uploads on mount and cleanup on unmount
onMounted(() => {
    fetchUploads();
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <Head title="CSV Uploads" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Upload Section -->
            <div
                class="border-2 rounded-lg p-6 transition-colors"
                :class="{
                    'border-blue-500 bg-blue-50': isDragging,
                    'border-gray-300': !isDragging
                }"
                @dragenter="handleDragEnter"
                @dragleave="handleDragLeave"
                @dragover="handleDragOver"
                @drop="handleDrop"
            >
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".csv"
                            multiple
                            class="hidden"
                            @change="handleFileUpload"
                        />
                        <div>
                            <span>
                                {{ isDragging ? 'Drop files here...' : 'Select file(s) or drag and drop' }}
                            </span>
                            <p v-if="uploadQueue > 0" class="text-sm text-gray-500 mt-1">
                                Uploading {{ uploadQueue }} file(s)...
                            </p>
                        </div>
                    </div>
                    <Button
                        @click="handleFileSelect"
                        :disabled="isUploading"
                        class="ml-4"
                    >
                        {{ isUploading ? 'Uploading...' : 'Upload File' }}
                    </Button>
                </div>
            </div>

            <!-- Upload History Table -->
            <div class="border rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-300 border-b">
                            <tr>
                                <th
                                    class="text-left p-4 font-medium text-gray-700 border cursor-pointer hover:bg-gray-400 transition-colors"
                                    @click="toggleSort('created_at')"
                                >
                                    <div class="flex items-center gap-2">
                                        Time
                                        <ArrowUpDown class="w-4 h-4" />
                                    </div>
                                </th>
                                <th
                                    class="text-left p-4 font-medium text-gray-700 cursor-pointer hover:bg-gray-400 transition-colors"
                                    @click="toggleSort('original_filename')"
                                >
                                    <div class="flex items-center gap-2">
                                        File Name
                                        <ArrowUpDown class="w-4 h-4" />
                                    </div>
                                </th>
                                <th class="text-left p-4 font-medium text-gray-700 border">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="sortedUploads.length === 0">
                                <td colspan="3" class="text-center py-8 text-gray-500">
                                    No uploads yet. Upload your first CSV file to get started.
                                </td>
                            </tr>
                            <tr
                                v-else
                                v-for="upload in sortedUploads"
                                :key="upload.id"
                                class="border-b"
                            >
                                <td class="p-4 border">
                                    {{ formatDate(upload.created_at) }}
                                </td>
                                <td class="p-4 font-medium">
                                    {{ upload.original_filename }}
                                </td>
                                <td class="p-4 border">
                                    <div class="space-y-2">
                                        <Badge :variant="getStatusVariant(upload.status)">
                                            {{ upload.status.toUpperCase() }}
                                        </Badge>

                                        <!-- Progress Bar for Processing -->
                                        <div v-if="upload.status === 'processing' && upload.total_rows" class="space-y-1">
                                            <div class="flex justify-between text-xs text-gray-600">
                                                <span>Processing...</span>
                                                <span>{{ upload.processed_rows }} / {{ upload.total_rows }} rows ({{ getProgress(upload) }}%)</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    class="bg-blue-500 h-2 rounded-full transition-all"
                                                    :style="{ width: `${getProgress(upload)}%` }"
                                                ></div>
                                            </div>
                                        </div>

                                        <!-- Completed Info -->
                                        <div v-if="upload.status === 'completed'" class="text-xs text-gray-500">
                                            Successfully processed {{ upload.processed_rows }} rows
                                        </div>

                                        <!-- Error Message -->
                                        <div v-if="upload.status === 'failed' && upload.error_message" class="text-xs text-red-500">
                                            Error: {{ upload.error_message }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
