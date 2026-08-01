import { ref } from 'vue';
import axios, { errorMessage, validationErrors } from '@/bootstrap';

/**
 * Shared list/upload/delete logic for the media library.
 *
 * Both the standalone library page and the picker modal need exactly this, and
 * duplicating it would mean fixing every upload bug twice.
 */
export function useMedia({ imagesOnly = false } = {}) {
    const items = ref([]);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });
    const loading = ref(false);
    const uploading = ref(false);
    const error = ref('');

    async function load({ search = '', page = 1 } = {}) {
        loading.value = true;
        error.value = '';

        const params = { page };
        if (search) params.search = search;
        if (imagesOnly) params.images_only = 1;

        try {
            const { data } = await axios.get('/api/media', { params });
            items.value = data.data;
            meta.value = data.meta;
        } catch (e) {
            error.value = errorMessage(e);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Uploads run one at a time rather than in parallel: a dozen simultaneous
     * multipart requests is how you find out PHP's max connections setting.
     */
    async function upload(files) {
        uploading.value = true;
        error.value = '';

        const uploaded = [];

        for (const file of files) {
            const body = new FormData();
            body.append('file', file);

            try {
                const { data } = await axios.post('/api/media', body);
                uploaded.push(data.data);
                items.value.unshift(data.data);
            } catch (e) {
                // Name the file that failed — "upload failed" alone is useless
                // when the user dropped fifteen of them.
                const detail = validationErrors(e).file ?? errorMessage(e);
                error.value = `${file.name}: ${detail}`;
            }
        }

        uploading.value = false;

        return uploaded;
    }

    async function remove(id) {
        try {
            await axios.delete(`/api/media/${id}`);
            items.value = items.value.filter((item) => item.id !== id);
        } catch (e) {
            error.value = errorMessage(e);
        }
    }

    return { items, meta, loading, uploading, error, load, upload, remove };
}

export function formatBytes(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}
