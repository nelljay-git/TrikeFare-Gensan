const SyncQueue = {
    QUEUE_KEY: 'trikefarePendingSync',

    push(request) {
        const queue = this.getQueue();
        queue.push({
            ...request,
            id: crypto.randomUUID(),
            timestamp: new Date().toISOString()
        });
        localStorage.setItem(this.QUEUE_KEY, JSON.stringify(queue));
        console.log('Request queued for offline sync:', request.type);
    },

    getQueue() {
        return JSON.parse(localStorage.getItem(this.QUEUE_KEY) || '[]');
    },

    async processQueue() {
        if (!navigator.onLine) return;
        
        const queue = this.getQueue();
        if (queue.length === 0) return;

        console.log(`Processing ${queue.length} pending sync requests...`);
        const remaining = [];

        for (const req of queue) {
            try {
                const success = await this.executeRequest(req);
                if (!success) remaining.push(req);
            } catch (e) {
                console.error('Failed to sync request:', e);
                remaining.push(req);
            }
        }

        localStorage.setItem(this.QUEUE_KEY, JSON.stringify(remaining));
        
        if (remaining.length === 0 && queue.length > 0) {
            if (typeof showToast === 'function') {
                showToast('All offline data synced successfully', 'success');
            }
        }
    },

    async executeRequest(req) {
        let endpoint = '';
        let options = {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(req.data)
        };

        switch (req.type) {
            case 'sync_history':
                endpoint = 'api/sync_history.php';
                break;
            case 'submit_fare':
                endpoint = 'api/community_submit.php';
                break;
            default:
                return true; // Unknown type, remove from queue
        }

        const res = await fetch(endpoint, options);
        const data = await res.json();
        return data.success;
    }
};

// Auto-process queue when coming back online
window.addEventListener('online', () => {
    document.getElementById('offlineBanner')?.classList.remove('show');
    document.body.classList.remove('is-offline');
    SyncQueue.processQueue();
});

window.addEventListener('offline', () => {
    document.getElementById('offlineBanner')?.classList.add('show');
    document.body.classList.add('is-offline');
});

// Initial check
if (!navigator.onLine) {
    setTimeout(() => {
        document.getElementById('offlineBanner')?.classList.add('show');
        document.body.classList.add('is-offline');
    }, 1000);
}
