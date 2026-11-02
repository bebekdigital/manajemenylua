import { ref } from 'vue';
import axios from 'axios';

// Module-level singleton state shared between TopNav (desktop) and SidebarNav (mobile bottom bar)
const pendingApprovalsCount = ref(0);
const pendingApprovalsList = ref([]);
const isLoadingApprovals = ref(false);
let approvalsPollInterval = null;
let subscribersCount = 0;

export function usePendingApprovals() {
    async function fetchPendingApprovals() {
        try {
            isLoadingApprovals.value = true;
            const response = await axios.get('/staff/permits/pending-approvals');
            if (response.data) {
                pendingApprovalsCount.value = response.data.count ?? 0;
                pendingApprovalsList.value = response.data.items ?? [];
            }
        } catch {
            // Ignore temporary network errors silently
        } finally {
            isLoadingApprovals.value = false;
        }
    }

    function startApprovalsPolling() {
        subscribersCount++;
        if (subscribersCount === 1) {
            fetchPendingApprovals();
            // Polling interval 4 seconds for realtime sync
            approvalsPollInterval = setInterval(fetchPendingApprovals, 4000);
        }
    }

    function stopApprovalsPolling() {
        subscribersCount = Math.max(0, subscribersCount - 1);
        if (subscribersCount === 0 && approvalsPollInterval) {
            clearInterval(approvalsPollInterval);
            approvalsPollInterval = null;
        }
    }

    function setCount(val) {
        if (typeof val === 'number') {
            pendingApprovalsCount.value = val;
        }
    }

    return {
        pendingApprovalsCount,
        pendingApprovalsList,
        isLoadingApprovals,
        fetchPendingApprovals,
        startApprovalsPolling,
        stopApprovalsPolling,
        setCount,
    };
}
