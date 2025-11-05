<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

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

const uploads = ref<FileUpload[]>([]);
const isUploading = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const pollingInterval = ref<number | null>(null);

const fetchUploads = async () => {
    try {
        const response = await axios.get('/api/uploads');
        uploads.value = response.data.data;
    } catch (error) {
        console.error('Error fetching uploads:', error);
    }
};

const handleFileSelect = () => {
    fileInput.value?.click();
};

const handleFileUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    // Validate file type
    if (!file.name.endsWith('.csv')) {
        alert('Please select a CSV file');
        return;
    }

    isUploading.value = true;

    const formData = new FormData();
    formData.append('file', file);

    try {
        await axios.post('/api/uploads', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        // Clear file input
        if (target) {
            target.value = '';
        }

        // Refresh the list
        await fetchUploads();
    } catch (error: any) {
        console.error('Error uploading file:', error);
        alert(error.response?.data?.message || 'Error uploading file');
    } finally {
        isUploading.value = false;
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-500';
        case 'processing':
            return 'bg-blue-500';
        case 'failed':
            return 'bg-red-500';
        default:
            return 'bg-gray-500';
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

// Poll for updates every 2 seconds
onMounted(() => {
    fetchUploads();
    pollingInterval.value = window.setInterval(fetchUploads, 2000);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
});
</script>

<template>
    <Head title="CSV Uploads" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Upload Section -->
            <Card>
                <CardHeader>
                    <CardTitle>Upload CSV File</CardTitle>
                    <CardDescription>
                        Upload a CSV file to process product data. The file will be processed in the background.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".csv"
                        class="hidden"
                        @change="handleFileUpload"
                    />
                    <Button
                        @click="handleFileSelect"
                        :disabled="isUploading"
                    >
                        {{ isUploading ? 'Uploading...' : 'Select CSV File' }}
                    </Button>
                </CardContent>
            </Card>

            <!-- Upload History -->
            <Card>
                <CardHeader>
                    <CardTitle>Upload History</CardTitle>
                    <CardDescription>
                        View all your recent CSV uploads and their processing status
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="uploads.length === 0" class="text-center py-8 text-muted-foreground">
                        No uploads yet. Upload your first CSV file to get started.
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="upload in uploads"
                            :key="upload.id"
                            class="border rounded-lg p-4 space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="font-semibold">{{ upload.original_filename }}</h3>
                                    <p class="text-sm text-muted-foreground">
                                        Uploaded: {{ formatDate(upload.created_at) }}
                                    </p>
                                </div>
                                <Badge :variant="getStatusVariant(upload.status)">
                                    {{ upload.status.toUpperCase() }}
                                </Badge>
                            </div>

                            <!-- Progress Bar for Processing -->
                            <div v-if="upload.status === 'processing' && upload.total_rows">
                                <div class="flex justify-between text-sm mb-1">
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
                            <div v-if="upload.status === 'completed'" class="text-sm text-muted-foreground">
                                Successfully processed {{ upload.processed_rows }} rows
                            </div>

                            <!-- Error Message -->
                            <div v-if="upload.status === 'failed' && upload.error_message" class="text-sm text-red-500">
                                Error: {{ upload.error_message }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
