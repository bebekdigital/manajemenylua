<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import OrgPositionNode from './OrgPositionNode.vue';

const props = defineProps({
    positions: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
    organizationLevels: {
        type: Array,
        default: () => [],
    },
    isActive: {
        type: Boolean,
        default: true,
    },
    canCreate: {
        type: Boolean,
        default: true,
    },
    canUpdate: {
        type: Boolean,
        default: true,
    },
    canDelete: {
        type: Boolean,
        default: true,
    },
});

const canEditAny = computed(() => {
    return Boolean(props.canCreate || props.canUpdate || props.canDelete);
});

const emit = defineEmits([
    'add-position',
    'edit-position',
    'delete-position',
    'add-department',
    'edit-department',
    'delete-department',
]);

const searchQuery = ref('');
const selectedDeptFilter = ref('all');
const activeTab = ref('diagram'); // 'diagram' | 'departments'
const hoveredPosId = ref(null);

const diagramContainerRef = ref(null);
const canvasWrapperRef = ref(null);
const tableRef = ref(null);
const connectionLines = ref([]);
const junctionDots = ref([]);
const svgWidth = ref(800);
const svgHeight = ref(600);
const zoomScale = ref(1);
const isAutoFit = ref(true);
const isEditMode = ref(false);

// State Pan & Zoom Canvas
const panX = ref(0);
const panY = ref(0);
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const hasDragged = ref(false);

let touchStartX = 0;
let touchStartY = 0;
let initialPinchDistance = null;
let initialPinchZoom = 1;

function startPan(e) {
    if (e.button !== 0) return;
    if (e.target.closest('button, input, select, a, [role="button"]')) return;
    isDragging.value = true;
    hasDragged.value = false;
    dragStart.value = {
        x: e.clientX - panX.value,
        y: e.clientY - panY.value,
    };
}

function onPan(e) {
    if (!isDragging.value) return;
    hasDragged.value = true;
    panX.value = Math.round(e.clientX - dragStart.value.x);
    panY.value = Math.round(e.clientY - dragStart.value.y);
}

function endPan() {
    isDragging.value = false;
}

function handleTouchStart(e) {
    if (e.touches.length === 1) {
        if (e.target.closest('button, input, select, a, [role="button"]')) return;
        isDragging.value = true;
        touchStartX = e.touches[0].clientX - panX.value;
        touchStartY = e.touches[0].clientY - panY.value;
    } else if (e.touches.length === 2) {
        isDragging.value = false;
        initialPinchDistance = Math.hypot(
            e.touches[0].clientX - e.touches[1].clientX,
            e.touches[0].clientY - e.touches[1].clientY
        );
        initialPinchZoom = zoomScale.value;
    }
}

function handleTouchMove(e) {
    if (e.touches.length === 1 && isDragging.value) {
        e.preventDefault();
        panX.value = Math.round(e.touches[0].clientX - touchStartX);
        panY.value = Math.round(e.touches[0].clientY - touchStartY);
    } else if (e.touches.length === 2 && initialPinchDistance) {
        e.preventDefault();
        const dist = Math.hypot(
            e.touches[0].clientX - e.touches[1].clientX,
            e.touches[0].clientY - e.touches[1].clientY
        );
        const factor = dist / initialPinchDistance;
        zoomScale.value = Math.min(1.5, Math.max(0.5, Number((initialPinchZoom * factor).toFixed(2))));
    }
}

function handleTouchEnd() {
    isDragging.value = false;
    initialPinchDistance = null;
}

function handleWheel(e) {
    if (e.ctrlKey || e.metaKey) {
        e.preventDefault();
        const delta = e.deltaY < 0 ? 0.05 : -0.05;
        zoomScale.value = Math.min(1.5, Math.max(0.5, Number((zoomScale.value + delta).toFixed(2))));
    } else {
        panY.value = Math.round(panY.value - e.deltaY * 0.7);
        panX.value = Math.round(panX.value - e.deltaX * 0.7);
    }
}

function setZoom(val) {
    isAutoFit.value = false;
    zoomScale.value = Math.min(1.5, Math.max(0.5, Number(val.toFixed(2))));
}

function resetView() {
    panX.value = 0;
    panY.value = 0;
    zoomScale.value = 1;
    isAutoFit.value = false;
    nextTick(updateConnectionLines);
}

function fitToView() {
    panX.value = 0;
    panY.value = 0;
    if (!diagramContainerRef.value || !tableRef.value) return;
    const containerWidth = diagramContainerRef.value.clientWidth;
    const tableWidth = tableRef.value.offsetWidth || 1000;
    if (tableWidth > containerWidth && containerWidth > 200) {
        zoomScale.value = Math.max(0.55, Math.min(1, Number(((containerWidth - 32) / tableWidth).toFixed(2))));
    } else {
        zoomScale.value = 1;
    }
}

watch(
    () => props.isActive,
    (active) => {
        if (active) {
            nextTick(() => {
                if (isAutoFit.value) {
                    fitToView();
                }
                nextTick(() => {
                    updateConnectionLines();
                    setTimeout(updateConnectionLines, 80);
                    setTimeout(updateConnectionLines, 250);
                });
            });
        }
    },
    { immediate: true }
);

function setEditMode(val) {
    if (val && !canEditAny.value) return;
    isEditMode.value = val;
    nextTick(updateConnectionLines);
}

watch(canEditAny, (newVal) => {
    if (!newVal && isEditMode.value) {
        isEditMode.value = false;
        nextTick(updateConnectionLines);
    }
});

function toggleAutoFit() {
    isAutoFit.value = true;
    fitToView();
    nextTick(updateConnectionLines);
}

const levelDefinitions = [
    {
        level: 1,
        title: 'Level 1: Dewan Pembina & Pengawas Yayasan',
        defaultName: 'Dewan Pembina & Pengawas Yayasan',
        subtitle: 'Pemegang otoritas tertinggi, arahan strategis, dan pengawasan yayasan',
        badgeClass: 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border-emerald-500/30',
        rowHeaderBg: 'bg-emerald-500/[0.04]',
        accentText: 'text-emerald-700 dark:text-emerald-400',
        iconBg: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
        btnClass: 'bg-emerald-600 hover:bg-emerald-700 text-white',
        cardBorder: 'hover:border-emerald-500/70',
        dotColor: 'bg-emerald-600',
        strokeColor: '#059669', // emerald-600
        icon: 'verified_user',
    },
    {
        level: 2,
        title: 'Level 2: Pengurus Harian & Direksi Yayasan',
        defaultName: 'Pengurus Harian & Direksi Yayasan',
        subtitle: 'Pimpinan harian & direksi: Ketua Umum, Sekretaris, Bendahara, Direktur Eksekutif',
        badgeClass: 'bg-indigo-500/15 text-indigo-800 dark:text-indigo-300 border-indigo-500/30',
        rowHeaderBg: 'bg-indigo-500/[0.04]',
        accentText: 'text-indigo-700 dark:text-indigo-400',
        iconBg: 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300',
        btnClass: 'bg-indigo-600 hover:bg-indigo-700 text-white',
        cardBorder: 'hover:border-indigo-500/70',
        dotColor: 'bg-indigo-600',
        strokeColor: '#4f46e5', // indigo-600
        icon: 'corporate_fare',
    },
    {
        level: 3,
        title: 'Level 3: Kepala Bidang Yayasan',
        defaultName: 'Kepala Bidang Yayasan',
        subtitle: 'Pimpinan bidang fungsional: Pendidikan, SDM, Keuangan, Sarpras, Dakwah & Humas',
        badgeClass: 'bg-amber-500/15 text-amber-800 dark:text-amber-300 border-amber-500/30',
        rowHeaderBg: 'bg-amber-500/[0.04]',
        accentText: 'text-amber-700 dark:text-amber-400',
        iconBg: 'bg-amber-500/15 text-amber-700 dark:text-amber-300',
        btnClass: 'bg-amber-600 hover:bg-amber-700 text-white',
        cardBorder: 'hover:border-amber-500/70',
        dotColor: 'bg-amber-600',
        strokeColor: '#d97706', // amber-600
        icon: 'manage_accounts',
    },
    {
        level: 4,
        title: 'Level 4: Koordinator & Kepala Bagian / Unit',
        defaultName: 'Koordinator & Kepala Bagian / Unit',
        subtitle: 'Koordinator operasional program kerja, pengawas sekolah, dan penjamin teknis',
        badgeClass: 'bg-cyan-500/15 text-cyan-800 dark:text-cyan-300 border-cyan-500/30',
        rowHeaderBg: 'bg-cyan-500/[0.04]',
        accentText: 'text-cyan-700 dark:text-cyan-400',
        iconBg: 'bg-cyan-500/15 text-cyan-700 dark:text-cyan-300',
        btnClass: 'bg-cyan-600 hover:bg-cyan-700 text-white',
        cardBorder: 'hover:border-cyan-500/70',
        dotColor: 'bg-cyan-600',
        strokeColor: '#0891b2', // cyan-600
        icon: 'hub',
    },
    {
        level: 5,
        title: 'Level 5: Staf Pelaksana & Tim Teknis',
        defaultName: 'Staf Pelaksana & Tim Teknis',
        subtitle: 'Staf pelaksana administrasi, logistik, pengadaan, dan teknis operasional harian',
        badgeClass: 'bg-slate-500/15 text-slate-800 dark:text-slate-300 border-slate-500/30',
        rowHeaderBg: 'bg-slate-500/[0.04]',
        accentText: 'text-slate-700 dark:text-slate-400',
        iconBg: 'bg-slate-500/15 text-slate-700 dark:text-slate-300',
        btnClass: 'bg-slate-700 hover:bg-slate-800 text-white',
        cardBorder: 'hover:border-slate-500/70',
        dotColor: 'bg-slate-600',
        strokeColor: '#475569', // slate-600
        icon: 'group',
    },
];

// Custom Level Names & Subtitles
const customLevels = ref({});
const showLevelEditModal = ref(false);
const editingLevel = ref(null);
const levelForm = ref({ name: '', subtitle: '', processing: false });

function initCustomLevels() {
    const map = {};
    levelDefinitions.forEach(d => {
        map[d.level] = {
            name: d.defaultName,
            subtitle: d.subtitle || '',
            isSaved: false,
        };
    });

    if (props.organizationLevels && Array.isArray(props.organizationLevels) && props.organizationLevels.length > 0) {
        props.organizationLevels.forEach(ol => {
            if (ol && ol.level) {
                map[ol.level] = {
                    name: ol.name || map[ol.level]?.name || '',
                    // Once a record is saved in DB, its subtitle is strictly ol.subtitle (empty string if null/undefined)
                    subtitle: ol.subtitle ? String(ol.subtitle).trim() : '',
                    isSaved: true,
                };
            }
        });
    }

    customLevels.value = map;
}

watch(() => props.organizationLevels, initCustomLevels, { immediate: true, deep: true });

function getLevelName(level) {
    return customLevels.value[level]?.name || levelDefinitions.find(d => d.level === level)?.defaultName || `Level ${level}`;
}

function getLevelSubtitle(level) {
    if (customLevels.value[level]) {
        if (customLevels.value[level].isSaved) {
            return customLevels.value[level].subtitle || '';
        }
        return customLevels.value[level].subtitle || '';
    }
    return levelDefinitions.find(d => d.level === level)?.subtitle || '';
}

function openEditLevelModal(level) {
    editingLevel.value = level;
    levelForm.value = {
        name: getLevelName(level),
        subtitle: getLevelSubtitle(level),
        processing: false,
    };
    showLevelEditModal.value = true;
}

function saveLevelEdit() {
    if (!levelForm.value.name.trim()) return;
    levelForm.value.processing = true;

    const level = editingLevel.value;
    const newName = levelForm.value.name.trim();
    const newSubtitle = levelForm.value.subtitle ? levelForm.value.subtitle.trim() : '';

    // Optimistic update
    customLevels.value[level] = {
        name: newName,
        subtitle: newSubtitle,
        isSaved: true,
    };

    router.put(`/portal/organization-levels/${level}`, {
        name: newName,
        subtitle: newSubtitle,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showLevelEditModal.value = false;
            levelForm.value.processing = false;
        },
        onError: () => {
            levelForm.value.processing = false;
        },
    });
}

// Raw map lookup by ID
const rawPositionsMap = computed(() => {
    const map = new Map();
    props.positions.forEach(p => map.set(p.id, p));
    return map;
});

// Map of children names grouped by parent_id
const childrenByParent = computed(() => {
    const map = new Map();
    props.positions.forEach(p => {
        if (p.parent_id) {
            if (!map.has(p.parent_id)) {
                map.set(p.parent_id, []);
            }
            map.get(p.parent_id).push(p.name);
        }
    });
    return map;
});

// Enriched positions with parent info and direct children info
const enrichedPositions = computed(() => {
    return props.positions.map(p => {
        const parent = p.parent_id ? rawPositionsMap.value.get(p.parent_id) : null;
        const children = childrenByParent.value.get(p.id) || [];
        const hasLower = props.positions.some(child =>
            child.parent_id === p.id && Number(child.level) > Number(p.level)
        );

        let posLevel = Number(p.level) || 3;
        // Pimpinan unit (Kepala Sekolah, Mudir, Kepala Madrasah, Kepala Unit) yang langsung di bawah yayasan/bidang (Level 2)
        // posisinya sejajar/setara di Level 3 (Manajemen Unit)
        const nameLower = (p.name || '').toLowerCase();
        const isUnitLeader = nameLower.includes('kepala sekolah') || nameLower.includes('mudir') || nameLower.includes('kepala madrasah') || nameLower.includes('kepala unit');
        if (isUnitLeader && (!p.parent_id || (parent && Number(parent.level) === 2))) {
            posLevel = 3;
        }

        return {
            ...p,
            level: posLevel,
            order: Number(p.order) || 0,
            parent_name: parent ? parent.name : p.parent_name || null,
            parent_level: parent ? (Number(parent.level) || null) : null,
            subordinates: children,
            has_lower_children: hasLower,
        };
    });
});

// Map lookup by ID using synchronized enriched positions
const positionsMap = computed(() => {
    const map = new Map();
    enrichedPositions.value.forEach(p => map.set(p.id, p));
    return map;
});

// Filtered positions based on search and department filter
const filteredPositions = computed(() => {
    return enrichedPositions.value.filter(p => {
        if (searchQuery.value) {
            const q = searchQuery.value.toLowerCase().trim();
            const matchName = p.name.toLowerCase().includes(q);
            const matchDept = p.department_name ? p.department_name.toLowerCase().includes(q) : false;
            const matchParent = p.parent_name ? p.parent_name.toLowerCase().includes(q) : false;
            if (!matchName && !matchDept && !matchParent) return false;
        }

        if (selectedDeptFilter.value !== 'all') {
            if (selectedDeptFilter.value === 'null') {
                if (p.department_id) return false;
            } else if (p.department_id !== Number(selectedDeptFilter.value)) {
                return false;
            }
        }

        return true;
    });
});

// Group filtered positions by level (1 to 5), sorted so children of same parent sit near each other
const positionsByLevel = computed(() => {
    const groups = { 1: [], 2: [], 3: [], 4: [], 5: [] };
    filteredPositions.value.forEach(p => {
        const lvl = Math.min(5, Math.max(1, p.level));
        if (!groups[lvl]) groups[lvl] = [];
        groups[lvl].push(p);
    });

    // Sort in each level by parent_id, then order, then name
    for (let l = 1; l <= 5; l++) {
        groups[l].sort((a, b) => {
            const pA = a.parent_id || 0;
            const pB = b.parent_id || 0;
            if (pA !== pB) return pA - pB;
            if (a.order !== b.order) return a.order - b.order;
            return a.name.localeCompare(b.name);
        });
    }

    return groups;
});

// Highest level that currently has at least 1 position
const maxActiveLevel = computed(() => {
    let maxL = 1;
    for (let l = 1; l <= 5; l++) {
        if (positionsByLevel.value[l] && positionsByLevel.value[l].length > 0) {
            maxL = l;
        }
    }
    return maxL;
});

// Only render active levels with positions (so empty levels at bottom don't inflate scroll height)
const visibleLevelDefinitions = computed(() => {
    return levelDefinitions.filter(def => def.level <= maxActiveLevel.value);
});

// Map of position ID -> array of child position objects in the SAME level
const intraChildrenMapByLevel = computed(() => {
    const map = {};
    const filteredSet = new Set(filteredPositions.value.map(p => p.id));

    filteredPositions.value.forEach(p => {
        if (p.parent_id && filteredSet.has(p.parent_id)) {
            const parent = positionsMap.value.get(p.parent_id);
            if (parent && Number(parent.level) === Number(p.level)) {
                if (!map[p.parent_id]) {
                    map[p.parent_id] = [];
                }
                map[p.parent_id].push(p);
            }
        }
    });

    // Sort children in each group
    Object.keys(map).forEach(parentId => {
        map[parentId].sort((a, b) => {
            if (a.order !== b.order) return a.order - b.order;
            return a.name.localeCompare(b.name);
        });
    });

    return map;
});

// For each level (1 to 5), determine root positions (intra-roots) to render at the top tier of that level
const intraRootsByLevel = computed(() => {
    const roots = { 1: [], 2: [], 3: [], 4: [], 5: [] };
    const intraMap = intraChildrenMapByLevel.value;

    for (let lvl = 1; lvl <= 5; lvl++) {
        const positionsInLevel = positionsByLevel.value[lvl] || [];
        const inLevelIds = new Set(positionsInLevel.map(p => p.id));
        const visited = new Set();

        // 1. A position is an intra-root if its parent_id is not in this level (or null)
        positionsInLevel.forEach(p => {
            const hasParentInThisLevel = p.parent_id && inLevelIds.has(p.parent_id) &&
                Number(positionsMap.value.get(p.parent_id)?.level) === lvl;

            if (!hasParentInThisLevel) {
                roots[lvl].push(p);
                // Mark p and all its descendants in this level as visited
                const markVisited = (nodeId) => {
                    visited.add(nodeId);
                    const kids = intraMap[nodeId] || [];
                    kids.forEach(k => {
                        if (!visited.has(k.id)) markVisited(k.id);
                    });
                };
                markVisited(p.id);
            }
        });

        // 2. Safety fallback: if there are any circular references or unvisited nodes in this level,
        // add them as roots so they are never lost/hidden!
        positionsInLevel.forEach(p => {
            if (!visited.has(p.id)) {
                roots[lvl].push(p);
                visited.add(p.id);
            }
        });

        // Sort roots
        roots[lvl].sort((a, b) => {
            const pA = a.parent_id || 0;
            const pB = b.parent_id || 0;
            if (pA !== pB) return pA - pB;
            if (a.order !== b.order) return a.order - b.order;
            return a.name.localeCompare(b.name);
        });
    }

    return roots;
});

// Helper mengambil koordinat unscaled kartu / baris relatif terhadap canvasWrapperRef
function getUnscaledRect(el) {
    if (!el || !canvasWrapperRef.value) return null;
    const wrapper = canvasWrapperRef.value;
    const s = (zoomScale.value && zoomScale.value > 0) ? zoomScale.value : 1;

    const r = el.getBoundingClientRect();
    if (r.width === 0 && r.height === 0) return null;
    const wr = wrapper.getBoundingClientRect();

    const x = (r.left - wr.left) / s;
    const y = (r.top - wr.top) / s;
    const width = r.width / s;
    const height = r.height / s;

    return {
        left: Math.round(x),
        top: Math.round(y),
        right: Math.round(x + width),
        bottom: Math.round(y + height),
        width: Math.round(width),
        height: Math.round(height),
        centerX: Math.round(x + width / 2),
        centerY: Math.round(y + height / 2),
    };
}

// ========================================================
// GARIS HIERARKI SVG DINAMIS (ANTAR ATASAN DAN BAWAHAN / SEJAJAR)
// MENGGUNAKAN POLA BUS ROUTING DENGAN KOORDINAT VIEWPORT TERSKALA
// ========================================================
function updateConnectionLines() {
    if (!diagramContainerRef.value || !tableRef.value || !canvasWrapperRef.value) return;

    const containerEl = diagramContainerRef.value;
    const tableEl = tableRef.value;

    const rawTableWidth = tableEl.offsetWidth || 800;
    const rawTableHeight = tableEl.offsetHeight || 600;
    const availableWidth = containerEl.clientWidth || rawTableWidth;

    const lines = [];
    const dotsMap = new Map();
    let jumpCount = 0;
    let maxCardRight = 0;

    // Cache unscaled bounding rects seluruh kartu yang aktif
    const cardRectsMap = new Map();
    props.positions.forEach(pos => {
        const el = document.getElementById(`pos-card-${pos.id}`);
        if (el) {
            const r = getUnscaledRect(el);
            if (r) {
                cardRectsMap.set(pos.id, r);
                if (r.right > maxCardRight) {
                    maxCardRight = r.right;
                }
            }
        }
    });

    svgWidth.value = Math.max(availableWidth, rawTableWidth, maxCardRight + 48);
    svgHeight.value = rawTableHeight;

    // Group filtered children by parent_id
    const childrenByParent = new Map();
    filteredPositions.value.forEach(pos => {
        if (!pos.parent_id) return;
        if (!childrenByParent.has(pos.parent_id)) {
            childrenByParent.set(pos.parent_id, []);
        }
        childrenByParent.get(pos.parent_id).push(pos);
    });

    // Proses setiap atasan (parent) yang memiliki bawahan
    childrenByParent.forEach((children, parentId) => {
        const parentRect = cardRectsMap.get(parentId);
        const parentEl = document.getElementById(`pos-card-${parentId}`);
        if (!parentRect || !parentEl) return;

        const parentLevel = Number(parentEl.dataset.level) || 1;
        const parentLevelDef = levelDefinitions.find(d => d.level === parentLevel) || levelDefinitions[0];
        const strokeColor = parentLevelDef.strokeColor;

        const sameLevelSubordinates = [];
        const peers = [];
        const nextLevelKids = [];
        const jumpLevelKids = [];

        children.forEach(child => {
            const childRect = cardRectsMap.get(child.id);
            const childEl = document.getElementById(`pos-card-${child.id}`);
            if (!childRect || !childEl) return;

            const childLevel = Number(childEl.dataset.level) || 1;

            if (childLevel === parentLevel) {
                if (childRect.top > parentRect.bottom - 8) {
                    sameLevelSubordinates.push({ pos: child, rect: childRect });
                } else {
                    peers.push({ pos: child, rect: childRect });
                }
            } else if (childLevel === parentLevel + 1) {
                nextLevelKids.push({ pos: child, rect: childRect });
            } else if (childLevel > parentLevel + 1) {
                jumpLevelKids.push({ pos: child, rect: childRect, level: childLevel });
            }
        });

        const x_p = parentRect.centerX;
        const y_p = parentRect.bottom;

        // ----------------------------------------------------
        // A. PEERS (SEJAJAR HORIZONTAL DI LEVEL YANG SAMA)
        // ----------------------------------------------------
        peers.forEach(({ pos: child, rect: childRect }) => {
            const parentIsLeft = parentRect.left < childRect.left;
            const x1 = parentIsLeft ? parentRect.right : parentRect.left;
            const y1 = parentRect.centerY;
            const x2 = parentIsLeft ? childRect.left : childRect.right;
            const y2 = childRect.centerY;

            let path = '';
            if (Math.abs(y1 - y2) < 6) {
                path = `M ${x1} ${y1} L ${x2} ${y2}`;
            } else {
                const midX = Math.round(x1 + (x2 - x1) * 0.5);
                path = `M ${x1} ${y1} L ${midX} ${y1} L ${midX} ${y2} L ${x2} ${y2}`;
            }

            lines.push({
                id: `line-peer-${parentId}-${child.id}`,
                parentId,
                childId: child.id,
                parentLevel: parentLevelDef.level,
                path,
                color: strokeColor,
                isPeer: true,
            });
        });

        // ----------------------------------------------------
        // B. SUBORDINAT DI LEVEL YANG SAMA (INTRA-LEVEL)
        // ----------------------------------------------------
        let intraMidY = null;
        if (sameLevelSubordinates.length > 0) {
            sameLevelSubordinates.sort((a, b) => a.rect.centerX - b.rect.centerX);
            const firstChild = sameLevelSubordinates[0];
            intraMidY = Math.round(y_p + (firstChild.rect.top - y_p) * 0.5);

            if (sameLevelSubordinates.length === 1) {
                const child = sameLevelSubordinates[0];
                const x2 = child.rect.centerX;
                const y2 = child.rect.top;
                let path = '';
                if (Math.abs(x_p - x2) <= 3) {
                    path = `M ${x_p} ${y_p} L ${x_p} ${y2}`;
                } else {
                    path = `M ${x_p} ${y_p} L ${x_p} ${intraMidY} L ${x2} ${intraMidY} L ${x2} ${y2}`;
                }
                lines.push({
                    id: `line-intra-${parentId}-${child.pos.id}`,
                    parentId,
                    childId: child.pos.id,
                    parentLevel: parentLevelDef.level,
                    path,
                    color: strokeColor,
                    isPeer: false,
                });
            } else {
                // Bus routing: 1 trunk + 1 horizontal bar + drop lines
                const minX = Math.min(...sameLevelSubordinates.map(k => k.rect.centerX));
                const maxX = Math.max(...sameLevelSubordinates.map(k => k.rect.centerX));
                const busLeft = Math.min(minX, x_p);
                const busRight = Math.max(maxX, x_p);

                // Trunk
                lines.push({
                    id: `line-intra-trunk-${parentId}`,
                    parentId,
                    childId: null,
                    parentLevel: parentLevelDef.level,
                    path: `M ${x_p} ${y_p} L ${x_p} ${intraMidY}`,
                    color: strokeColor,
                    isPeer: false,
                });

                // Horizontal Bus Bar
                lines.push({
                    id: `line-intra-bus-${parentId}`,
                    parentId,
                    childId: null,
                    parentLevel: parentLevelDef.level,
                    path: `M ${busLeft} ${intraMidY} L ${busRight} ${intraMidY}`,
                    color: strokeColor,
                    isPeer: false,
                });

                // Dot di cabang trunk
                dotsMap.set(`dot-intra-${parentId}-${intraMidY}`, {
                    id: `dot-intra-${parentId}-${intraMidY}`,
                    cx: x_p,
                    cy: intraMidY,
                    color: strokeColor,
                });

                // Drop line ke masing-masing bawahan satu level
                sameLevelSubordinates.forEach(({ pos: child, rect: childRect }) => {
                    const x2 = childRect.centerX;
                    const y2 = childRect.top;
                    lines.push({
                        id: `line-intra-drop-${parentId}-${child.id}`,
                        parentId,
                        childId: child.id,
                        parentLevel: parentLevelDef.level,
                        path: `M ${x2} ${intraMidY} L ${x2} ${y2}`,
                        color: strokeColor,
                        isPeer: false,
                    });

                    dotsMap.set(`dot-intra-drop-${child.id}-${intraMidY}`, {
                        id: `dot-intra-drop-${child.id}-${intraMidY}`,
                        cx: x2,
                        cy: intraMidY,
                        color: strokeColor,
                    });
                });
            }
        }

        // ----------------------------------------------------
        // C. SUBORDINAT DI LEVEL BAWAHNYA LANGSUNG (NEXT-LEVEL)
        // ----------------------------------------------------
        if (nextLevelKids.length > 0) {
            nextLevelKids.sort((a, b) => a.rect.centerX - b.rect.centerX);

            const parentRowEl = document.getElementById(`level-row-${parentLevel}`);
            const rowRect = parentRowEl ? getUnscaledRect(parentRowEl) : null;
            const borderY = rowRect ? rowRect.bottom : Math.round(y_p + (nextLevelKids[0].rect.top - y_p) * 0.5);

            // Jika ada bawahan satu level, trunk melanjutkan dari intraMidY; jika tidak ada, dari y_p
            const trunkStartY = intraMidY !== null ? intraMidY : y_p;

            if (nextLevelKids.length === 1) {
                const child = nextLevelKids[0];
                const x2 = child.rect.centerX;
                const y2 = child.rect.top;
                let path = '';
                if (Math.abs(x_p - x2) <= 3) {
                    path = `M ${x_p} ${trunkStartY} L ${x_p} ${y2}`;
                } else {
                    path = `M ${x_p} ${trunkStartY} L ${x_p} ${borderY} L ${x2} ${borderY} L ${x2} ${y2}`;
                }
                lines.push({
                    id: `line-next-${parentId}-${child.pos.id}`,
                    parentId,
                    childId: child.pos.id,
                    parentLevel: parentLevelDef.level,
                    path,
                    color: strokeColor,
                    isPeer: false,
                });
            } else {
                // Bus routing: 1 trunk + 1 horizontal bar tepat di batas level row + drop lines
                const minX = Math.min(...nextLevelKids.map(k => k.rect.centerX));
                const maxX = Math.max(...nextLevelKids.map(k => k.rect.centerX));
                const busLeft = Math.min(minX, x_p);
                const busRight = Math.max(maxX, x_p);

                // Trunk
                lines.push({
                    id: `line-next-trunk-${parentId}`,
                    parentId,
                    childId: null,
                    parentLevel: parentLevelDef.level,
                    path: `M ${x_p} ${trunkStartY} L ${x_p} ${borderY}`,
                    color: strokeColor,
                    isPeer: false,
                });

                // Horizontal Bus Bar
                lines.push({
                    id: `line-next-bus-${parentId}`,
                    parentId,
                    childId: null,
                    parentLevel: parentLevelDef.level,
                    path: `M ${busLeft} ${borderY} L ${busRight} ${borderY}`,
                    color: strokeColor,
                    isPeer: false,
                });

                // Dot di cabang trunk
                dotsMap.set(`dot-next-${parentId}-${borderY}`, {
                    id: `dot-next-${parentId}-${borderY}`,
                    cx: x_p,
                    cy: borderY,
                    color: strokeColor,
                });

                // Drop line ke masing-masing bawahan level bawah
                nextLevelKids.forEach(({ pos: child, rect: childRect }) => {
                    const x2 = childRect.centerX;
                    const y2 = childRect.top;
                    lines.push({
                        id: `line-next-drop-${parentId}-${child.id}`,
                        parentId,
                        childId: child.id,
                        parentLevel: parentLevelDef.level,
                        path: `M ${x2} ${borderY} L ${x2} ${y2}`,
                        color: strokeColor,
                        isPeer: false,
                    });

                    dotsMap.set(`dot-next-drop-${child.id}-${borderY}`, {
                        id: `dot-next-drop-${child.id}-${borderY}`,
                        cx: x2,
                        cy: borderY,
                        color: strokeColor,
                    });
                });
            }
        }

        // ----------------------------------------------------
        // D. KASUS LOMPAT LEVEL (JUMP-LEVEL)
        // ----------------------------------------------------
        jumpLevelKids.forEach(({ pos: child, rect: childRect }) => {
            const x2 = childRect.centerX;
            const y2 = childRect.top;
            const rightCorridorBase = Math.max(maxCardRight + 24, rawTableWidth - 48);
            const x_right = Math.round(rightCorridorBase + (jumpCount * 12));
            jumpCount++;

            const y_exit = y_p + 14;
            const y_entry = y2 - 14;

            const path = `M ${x_p} ${y_p} L ${x_p} ${y_exit} L ${x_right} ${y_exit} L ${x_right} ${y_entry} L ${x2} ${y_entry} L ${x2} ${y2}`;

            lines.push({
                id: `line-jump-${parentId}-${child.id}`,
                parentId,
                childId: child.id,
                parentLevel: parentLevelDef.level,
                path,
                color: strokeColor,
                isPeer: false,
            });
        });
    });

    connectionLines.value = lines;
    junctionDots.value = Array.from(dotsMap.values());
}

// Watch positions and re-render lines
watch(
    () => [props.positions, searchQuery.value, selectedDeptFilter.value, activeTab.value],
    () => {
        nextTick(() => {
            updateConnectionLines();
            // Second pass after browser layout settles
            setTimeout(updateConnectionLines, 100);
        });
    },
    { deep: true }
);

// Re-render lines on zoom scale change
watch(zoomScale, () => {
    nextTick(() => {
        updateConnectionLines();
    });
});

let resizeObserver = null;
let intersectionObserver = null;
onMounted(() => {
    nextTick(() => {
        updateConnectionLines();
        setTimeout(updateConnectionLines, 80);
        setTimeout(updateConnectionLines, 250);
    });

    window.addEventListener('resize', updateConnectionLines);

    if (window.ResizeObserver) {
        resizeObserver = new ResizeObserver(() => {
            updateConnectionLines();
        });
        if (diagramContainerRef.value) resizeObserver.observe(diagramContainerRef.value);
        if (tableRef.value) resizeObserver.observe(tableRef.value);
        if (canvasWrapperRef.value) resizeObserver.observe(canvasWrapperRef.value);
    }

    if (window.IntersectionObserver && diagramContainerRef.value) {
        intersectionObserver = new IntersectionObserver((entries) => {
            const entry = entries[0];
            if (entry && entry.isIntersecting) {
                updateConnectionLines();
                setTimeout(updateConnectionLines, 80);
            }
        });
        intersectionObserver.observe(diagramContainerRef.value);
    }
});

onUnmounted(() => {
    if (resizeObserver) resizeObserver.disconnect();
    if (intersectionObserver) intersectionObserver.disconnect();
    window.removeEventListener('resize', updateConnectionLines);
});

// Global add new position
function handleAddDirect() {
    emit('add-position', {
        presetLevel: 1,
        presetParentId: null,
        presetDepartmentId: null,
        modeTitle: 'Tambah Jabatan Baru',
    });
}

// Add directly inside a specific level row
function handleAddAtLevel(level) {
    emit('add-position', {
        presetLevel: level,
        presetParentId: null,
        presetDepartmentId: null,
        modeTitle: `Tambah Jabatan di Level ${level}`,
    });
}

// Add peer (sejajar) at the same level
function handleAddPeer(pos) {
    emit('add-position', {
        presetLevel: Number(pos.level) || 1,
        presetParentId: pos.parent_id || pos.id,
        presetDepartmentId: pos.department_id || null,
        modeTitle: `Tambah Jabatan Sejajar di Level ${pos.level}`,
    });
}

// Add subordinate (langsung buka modal, level bisa dipilih langsung di modal termasuk loncat level)
function handleAddSubordinate(pos) {
    const defaultLevel = Math.min(5, (Number(pos.level) || 1) + 1);
    emit('add-position', {
        presetLevel: defaultLevel,
        presetParentId: pos.id,
        presetDepartmentId: pos.department_id || null,
        modeTitle: `Tambah Bawahan untuk: ${pos.name}`,
    });
}

function handleEdit(pos) {
    emit('edit-position', pos);
}

function handleDelete(pos) {
    emit('delete-position', pos);
}
</script>

<template>
    <div class="space-y-6">
        <!-- Main Table Container: Seluruh Baris Level Menyatu Seperti Baris Tabel -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-sm overflow-hidden">
            <!-- Header Section Toolbar -->
            <div class="px-5 py-3.5 sm:px-6 sm:py-4 border-b border-outline-variant/40 bg-surface-container-low/30">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 sm:gap-4">
                    <div class="flex items-start sm:items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shadow-xs shrink-0">
                            <span class="material-symbols-outlined text-xl">account_tree</span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-on-surface">Diagram Susunan Organisasi</h3>
                            <p class="text-xs text-on-surface-variant">
                                Struktur hierarki organisasi dengan garis penghubung langsung antara atasan dan bawahan
                            </p>
                        </div>
                    </div>

                    <!-- Top Action Buttons: Mode View / Edit Switcher -->
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <div v-if="activeTab === 'diagram'" class="inline-flex items-center p-1 bg-slate-100 border border-slate-200 rounded-xl shrink-0">
                            <button
                                type="button"
                                @click="setEditMode(false)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                :class="!isEditMode
                                    ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900 border border-transparent'"
                                title="Mode Lihat Struktur Organisasi"
                            >
                                <span class="material-symbols-outlined text-[16px]">visibility</span>
                                <span>Mode Lihat</span>
                            </button>
                            <button
                                v-if="canEditAny"
                                type="button"
                                @click="setEditMode(true)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                :class="isEditMode
                                    ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900 border border-transparent'"
                                title="Mode Edit Organisasi (kelola posisi & hierarki)"
                            >
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                <span>Mode Edit</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters & View Switcher Bar -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mt-3 pt-3 border-t border-outline-variant/20">
                    <!-- Search & Filter -->
                    <div class="flex items-center gap-2.5 flex-1 flex-wrap">
                        <div class="relative flex-1 min-w-[220px]">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">
                                search
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama jabatan, bidang, atau atasan..."
                                class="w-full pl-9 pr-3 py-1.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all"
                            />
                        </div>

                        <select
                            v-model="selectedDeptFilter"
                            class="px-3 py-1.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary cursor-pointer"
                        >
                            <option value="all">Semua Bidang Kerja</option>
                            <option value="null">Yayasan / Lintas Bidang</option>
                            <option v-for="dept in departments" :key="dept.id" :value="String(dept.id)">
                                {{ dept.name }}
                            </option>
                        </select>
                    </div>

                    <!-- View Switcher & Zoom Controls -->
                    <div class="flex items-center gap-2 flex-wrap self-start md:self-auto">
                        <!-- Zoom Controls (Hanya saat tab diagram aktif) -->
                        <div
                            v-if="activeTab === 'diagram'"
                            class="inline-flex items-center gap-1 p-1 bg-slate-100 border border-slate-200 rounded-xl shrink-0"
                        >
                            <button
                                type="button"
                                @click="setZoom(zoomScale - 0.1)"
                                class="w-6 h-6 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:text-slate-900 transition-colors cursor-pointer"
                                title="Perkecil diagram"
                            >
                                <span class="material-symbols-outlined text-[15px]">remove</span>
                            </button>
                            <button
                                type="button"
                                @click="toggleAutoFit"
                                :class="[
                                    'px-2 py-0.5 rounded-lg text-[11px] font-bold transition-colors cursor-pointer',
                                    isAutoFit
                                        ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                        : 'bg-white text-slate-700 hover:bg-slate-50'
                                ]"
                                title="Otomatis sesuaikan lebar layar (Auto Fit)"
                            >
                                {{ Math.round(zoomScale * 100) }}%
                            </button>
                            <button
                                type="button"
                                @click="setZoom(zoomScale + 0.1)"
                                class="w-6 h-6 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:text-slate-900 transition-colors cursor-pointer"
                                title="Perbesar diagram"
                            >
                                <span class="material-symbols-outlined text-[15px]">add</span>
                            </button>
                        </div>

                        <!-- View Switcher -->
                        <div class="inline-flex items-center p-1 bg-slate-100 border border-slate-200 rounded-xl shrink-0">
                            <button
                                type="button"
                                @click="activeTab = 'diagram'"
                                class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                :class="activeTab === 'diagram'
                                    ? 'bg-emerald-100 text-emerald-950 border border-emerald-300 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900 border border-transparent'"
                            >
                                <span class="material-symbols-outlined text-[16px]">view_agenda</span>
                                <span>Diagram Hierarki</span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'departments'"
                                class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                :class="activeTab === 'departments'
                                    ? 'bg-emerald-100 text-emerald-950 border border-emerald-300 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900 border border-transparent'"
                            >
                                <span class="material-symbols-outlined text-[16px]">domain</span>
                                <span>Daftar Unit</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- CANVAS INTERAKTIF: BEBAS GESER (PAN) & ZOOM TANPA BATAS -->
            <!-- ======================================================== -->
            <div
                v-if="activeTab === 'diagram'"
                ref="diagramContainerRef"
                class="relative overflow-hidden w-full min-h-[620px] h-[72vh] max-h-[840px] bg-slate-50/60 select-none border-b border-slate-200/80"
                :class="isDragging ? 'cursor-grabbing' : 'cursor-grab'"
                style="background-image: radial-gradient(rgba(148, 163, 184, 0.28) 1.25px, transparent 1.25px); background-size: 24px 24px;"
                @mousedown="startPan"
                @mousemove="onPan"
                @mouseup="endPan"
                @mouseleave="endPan"
                @touchstart="handleTouchStart"
                @touchmove="handleTouchMove"
                @touchend="handleTouchEnd"
                @wheel="handleWheel"
            >
                <!-- Floating Canvas Controls (Bottom Right) -->
                <div class="absolute bottom-4 right-4 z-30 inline-flex items-center gap-1 p-1 bg-white/95 backdrop-blur-md rounded-2xl border border-slate-200 shadow-md">
                    <button
                        type="button"
                        @click.stop="setZoom(zoomScale - 0.1)"
                        class="w-7 h-7 rounded-xl flex items-center justify-center text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                        title="Perkecil Canvas"
                    >
                        <span class="material-symbols-outlined text-[16px]">remove</span>
                    </button>
                    <button
                        type="button"
                        @click.stop="resetView"
                        class="px-2.5 py-1 rounded-xl text-xs font-bold text-slate-800 hover:bg-slate-100 transition-colors cursor-pointer"
                        title="Reset ke 100% dan posisi tengah"
                    >
                        {{ Math.round(zoomScale * 100) }}%
                    </button>
                    <button
                        type="button"
                        @click.stop="setZoom(zoomScale + 0.1)"
                        class="w-7 h-7 rounded-xl flex items-center justify-center text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                        title="Perbesar Canvas"
                    >
                        <span class="material-symbols-outlined text-[16px]">add</span>
                    </button>
                    <div class="h-4 w-px bg-slate-200 mx-0.5"></div>
                    <button
                        type="button"
                        @click.stop="fitToView"
                        class="w-7 h-7 rounded-xl flex items-center justify-center text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                        title="Pusatkan Diagram (Fit View)"
                    >
                        <span class="material-symbols-outlined text-[17px]">fit_screen</span>
                    </button>
                </div>

                <!-- Floating Canvas Hint (Bottom Left) -->
                <div class="absolute bottom-4 left-4 z-20 hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/90 backdrop-blur-md border border-slate-200/80 shadow-xs text-[11px] text-slate-600 font-medium pointer-events-none">
                    <span class="material-symbols-outlined text-[15px] text-primary">drag_pan</span>
                    <span>Klik & geser canvas untuk berpindah area</span>
                </div>

                <!-- CANVAS STAGE: PAPAN DIAGRAM LENGKAP DENGAN SVG & TABEL KARTU -->
                <div
                    ref="canvasWrapperRef"
                    class="relative w-max min-w-full origin-top-center"
                    :style="{
                        transform: `translate(${panX}px, ${panY}px) scale(${zoomScale})`,
                        transformOrigin: 'top center',
                    }"
                >
                <!-- KANVAS SVG GARIS HIERARKI PENGHUBUNG ANTARA ATASAN DAN BAWAHAN -->
                <svg
                    class="absolute inset-0 pointer-events-none z-10 w-full h-full overflow-visible"
                    style="shape-rendering: geometricPrecision;"
                >
                    <!-- Render Setiap Garis Penghubung (Parent -> Child / Peer) bersudut 90 derajat -->
                    <g v-for="line in connectionLines" :key="line.id">
                        <!-- Glow / Shadow Line hanya saat di-hover -->
                        <path
                            v-if="hoveredPosId === line.parentId || hoveredPosId === line.childId"
                            :d="line.path"
                            stroke="rgba(5, 150, 105, 0.35)"
                            stroke-width="6"
                            fill="none"
                            stroke-linejoin="miter"
                            class="transition-colors duration-150"
                        />
                        <!-- Garis Utama Penghubung (Bersudut 90 Derajat / Sejajar Horizontal) -->
                        <path
                            :d="line.path"
                            :stroke="hoveredPosId === line.parentId || hoveredPosId === line.childId ? '#059669' : line.color"
                            :stroke-width="hoveredPosId === line.parentId || hoveredPosId === line.childId ? 2.5 : 2"
                            :stroke-opacity="hoveredPosId && hoveredPosId !== line.parentId && hoveredPosId !== line.childId ? 0.3 : 1"
                            fill="none"
                            stroke-linejoin="miter"
                            stroke-linecap="square"
                            class="transition-colors duration-150"
                        />
                    </g>

                    <!-- Junction Dots pada percabangan garis horizontal hierarki bawahan -->
                    <circle
                        v-for="dot in junctionDots"
                        :key="dot.id"
                        :cx="dot.cx"
                        :cy="dot.cy"
                        r="3.8"
                        :fill="dot.color"
                        stroke="#ffffff"
                        stroke-width="1.75"
                        class="transition-all duration-150 shadow-xs"
                    />
                </svg>

                <!-- TABEL / CONTAINER BARIS LEVEL YANG MENYATU SECARA VERTIKAL -->
                <table ref="tableRef" class="w-full border-collapse text-left">
                    <tbody>
                        <tr
                            v-for="(def, idx) in visibleLevelDefinitions"
                            :key="def.level"
                            :id="`level-row-${def.level}`"
                            class="border-b border-slate-200/80 last:border-b-0 hover:bg-slate-50/15 transition-colors relative"
                        >
                            <!-- CONTAINER LEVEL: POSISI LEVEL DI POJOK KIRI ATAS CONTAINER -->
                            <td class="px-3 pt-8 pb-16 sm:px-6 sm:pt-10 sm:pb-20 align-top relative" :class="def.rowHeaderBg">
                                <!-- BARIS LABEL LEVEL DI KIRI ATAS (RAPI & TIDAK MENABRAK CARD) -->
                                <div class="mb-2 inline-flex items-center gap-1.5 flex-wrap select-none bg-white/90 backdrop-blur-2xs px-2 py-0.5 rounded-lg border border-slate-200/80 shadow-2xs relative z-20">
                                    <span :class="['text-[9px] sm:text-[10px] font-bold px-1.5 py-0.5 rounded border shadow-2xs', def.badgeClass]">
                                        Level {{ def.level }}
                                    </span>
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800">
                                        {{ getLevelName(def.level) }}
                                    </span>
                                    <span class="text-[9.5px] sm:text-[10px] text-slate-500 font-medium">
                                        ({{ positionsByLevel[def.level]?.length || 0 }})
                                    </span>
                                    <!-- Tombol Edit Ringkas (Hanya di Mode Edit) -->
                                    <button
                                        v-if="isEditMode && canUpdate"
                                        type="button"
                                        @click="openEditLevelModal(def.level)"
                                        class="inline-flex items-center p-0.5 text-slate-500 hover:text-primary rounded hover:bg-slate-100 transition-colors cursor-pointer"
                                        :title="`Edit Nama Level ${def.level}`"
                                    >
                                        <span class="material-symbols-outlined text-[12px] sm:text-[13px]">edit</span>
                                    </button>
                                    <!-- Tombol Tambah Ringkas (Hanya di Mode Edit) -->
                                    <button
                                        v-if="isEditMode && canCreate"
                                        type="button"
                                        @click="handleAddAtLevel(def.level)"
                                        class="ml-0.5 inline-flex items-center gap-0.5 px-1.5 sm:px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-semibold text-white transition-all shadow-2xs hover:shadow cursor-pointer"
                                        :class="def.btnClass"
                                        :title="`Tambah Jabatan di Level ${def.level}`"
                                    >
                                        <span class="material-symbols-outlined text-[12px]">add</span>
                                        <span>Tambah</span>
                                    </button>
                                </div>

                                <!-- SETIAP CARD JABATAN DIMULAI DARI TENGAH & SEJAJAR HORIZONTAL (flex-nowrap) -->
                                <div
                                    v-if="intraRootsByLevel[def.level] && intraRootsByLevel[def.level].length > 0"
                                    class="flex items-start justify-center gap-4 sm:gap-6 flex-nowrap pt-2 pb-1 w-max min-w-full mx-auto"
                                >
                                    <OrgPositionNode
                                        v-for="rootPos in intraRootsByLevel[def.level]"
                                        :key="rootPos.id"
                                        :pos="rootPos"
                                        :level-def="def"
                                        :intra-children-map="intraChildrenMapByLevel"
                                        :hovered-pos-id="hoveredPosId"
                                        :is-edit-mode="isEditMode"
                                        :can-create="canCreate"
                                        :can-update="canUpdate"
                                        :can-delete="canDelete"
                                        @hover="hoveredPosId = $event"
                                        @leave="hoveredPosId = null"
                                        @add-subordinate="handleAddSubordinate"
                                        @add-peer="handleAddPeer"
                                        @edit="handleEdit"
                                        @delete="handleDelete"
                                    />
                                </div>

                                <!-- EMPTY STATE SAAT BARIS LEVEL BELUM MEMILIKI JABATAN -->
                                <div
                                    v-else
                                    class="py-2.5 px-3.5 border border-dashed border-slate-300 rounded-xl bg-white/60 flex items-center justify-between gap-3 max-w-sm mx-auto"
                                >
                                    <p class="text-[11px] sm:text-xs font-medium text-slate-500">
                                        Belum ada jabatan di Level {{ def.level }}.
                                    </p>
                                    <button
                                        v-if="isEditMode && canCreate"
                                        type="button"
                                        @click="handleAddAtLevel(def.level)"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold transition-all shadow-xs cursor-pointer shrink-0"
                                        :class="def.btnClass"
                                    >
                                        <span class="material-symbols-outlined text-[12px]">add</span>
                                        <span>Tambah</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Baris Tambah Level Selanjutnya (Jika belum sampai Level 5 dan di Mode Edit) -->
            <div
                v-if="activeTab === 'diagram' && isEditMode && canCreate && maxActiveLevel < 5"
                class="px-5 py-2.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs"
            >
                <span class="text-slate-600 font-medium">
                    Tingkat organisasi aktif: Level 1 s/d Level {{ maxActiveLevel }}
                </span>
                <button
                    type="button"
                    @click="handleAddAtLevel(maxActiveLevel + 1)"
                    class="inline-flex items-center gap-1 text-primary font-semibold hover:underline cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[15px]">add_circle</span>
                    <span>Tambah Jabatan di Level {{ maxActiveLevel + 1 }}</span>
                </button>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 2: DAFTAR UNIT KERJA YAYASAN                          -->
            <!-- ======================================================== -->
            <div v-else-if="activeTab === 'departments'" class="p-4 sm:p-6 space-y-4">
                <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100 flex-wrap">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block shrink-0"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Daftar Unit Kerja Yayasan</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200/80">
                                {{ departments.length }} Unit
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Kelola nama unit dan pembagian kelompok posisi di bawah naungan yayasan</p>
                    </div>
                    <button
                        v-if="canCreate"
                        type="button"
                        @click="$emit('add-department')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 text-xs sm:text-sm font-bold shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">add</span>
                        <span>Tambah Unit</span>
                    </button>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-hidden border border-slate-200/90 rounded-2xl shadow-2xs">
                    <table class="w-full text-xs sm:text-sm border-collapse text-left">
                        <thead class="bg-slate-50/90 text-slate-700 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200/90">
                            <tr>
                                <th class="py-3.5 px-5 w-1/4">Unit Kerja</th>
                                <th class="py-3.5 px-4 w-48">Kelompok Unit</th>
                                <th class="py-3.5 px-5">Jabatan Terdaftar di Unit</th>
                                <th class="py-3.5 px-3 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr
                                v-for="dept in departments"
                                :key="dept.id"
                                class="hover:bg-slate-50/70 transition-colors"
                            >
                                <td class="px-5 py-3.5 align-top font-bold text-slate-900">
                                    {{ dept.name }}
                                </td>
                                <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border',
                                            dept.unit_group === 'Satuan Pendidikan'
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                                : 'bg-blue-50 text-blue-800 border-blue-200'
                                        ]"
                                    >
                                        <span
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="dept.unit_group === 'Satuan Pendidikan' ? 'bg-emerald-600' : 'bg-blue-600'"
                                        ></span>
                                        <span>{{ dept.unit_group || 'Yayasan' }}</span>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 align-top">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="p in positions.filter(x => x.department_id === dept.id)"
                                            :key="p.id"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-slate-50 border border-slate-200 text-slate-800 font-medium"
                                        >
                                            <span class="text-[9px] font-bold px-1 rounded bg-emerald-100 text-emerald-800">
                                                L{{ p.level || 3 }}
                                            </span>
                                            <span>{{ p.name }}</span>
                                        </span>
                                        <span
                                            v-if="!positions.some(x => x.department_id === dept.id)"
                                            class="text-xs text-slate-400 italic"
                                        >
                                            Belum ada jabatan di unit ini
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 align-top text-center">
                                    <div class="inline-flex items-center justify-center gap-1">
                                        <button
                                            v-if="canUpdate"
                                            type="button"
                                            @click="$emit('edit-department', dept)"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors cursor-pointer"
                                            title="Edit Unit"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <button
                                            v-if="canDelete"
                                            type="button"
                                            @click="$emit('delete-department', dept)"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Unit"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                        <span v-if="!canUpdate && !canDelete" class="text-xs text-slate-400 italic">-</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="departments.length === 0">
                                <td colspan="4" class="px-5 py-10 text-center text-slate-400 italic">
                                    Belum ada unit terdaftar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="md:hidden space-y-3">
                    <div
                        v-for="dept in departments"
                        :key="dept.id"
                        class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/80 space-y-3 hover:border-slate-300 transition-colors shadow-2xs"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <h5 class="text-sm font-bold text-slate-900 leading-snug">
                                {{ dept.name }}
                            </h5>
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold border shrink-0',
                                    dept.unit_group === 'Satuan Pendidikan'
                                        ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                        : 'bg-blue-50 text-blue-800 border-blue-200'
                                ]"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="dept.unit_group === 'Satuan Pendidikan' ? 'bg-emerald-600' : 'bg-blue-600'"
                                ></span>
                                <span>{{ dept.unit_group || 'Yayasan' }}</span>
                            </span>
                        </div>

                        <div class="text-xs pt-1 border-t border-slate-200/60">
                            <span class="text-[11px] text-slate-400 block font-medium mb-1.5">Jabatan Terdaftar:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="p in positions.filter(x => x.department_id === dept.id)"
                                    :key="p.id"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-white border border-slate-200 text-slate-800 font-medium"
                                >
                                    <span class="text-[9px] font-bold px-1 rounded bg-emerald-100 text-emerald-800">
                                        L{{ p.level || 3 }}
                                    </span>
                                    <span>{{ p.name }}</span>
                                </span>
                                <span
                                    v-if="!positions.some(x => x.department_id === dept.id)"
                                    class="text-xs text-slate-400 italic"
                                >
                                    Belum ada jabatan di unit ini
                                </span>
                            </div>
                        </div>

                        <div v-if="canUpdate || canDelete" class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200/60">
                            <button
                                v-if="canUpdate"
                                type="button"
                                @click="$emit('edit-department', dept)"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[15px]">edit</span>
                                <span>Edit</span>
                            </button>
                            <button
                                v-if="canDelete"
                                type="button"
                                @click="$emit('delete-department', dept)"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                <span>Hapus</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="departments.length === 0" class="py-10 text-center text-slate-400 italic">
                        Belum ada unit terdaftar.
                    </div>
                </div>
            </div>
        </div>

        <!-- Pop-up Modal Edit Nama Level -->
        <Teleport to="body">
            <div
                v-if="showLevelEditModal"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
                @click.self="showLevelEditModal = false"
            >
                <div class="bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-md border border-outline-variant/30 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 pt-7 pb-5 sm:pt-8 sm:pb-6 border-b border-outline-variant/30 bg-surface-container-low/40">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shadow-xs">
                                <span class="material-symbols-outlined text-xl">edit_note</span>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-on-surface">Edit Nama Level {{ editingLevel }}</h3>
                                <p class="text-xs text-on-surface-variant">Ubah nama tingkatan pada container level</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="showLevelEditModal = false"
                            class="p-2 rounded-full text-on-surface-variant hover:bg-surface-variant transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-xl">close</span>
                        </button>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="saveLevelEdit" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Nama / Judul Tingkat Level <span class="text-error">*</span>
                            </label>
                            <input
                                v-model="levelForm.name"
                                type="text"
                                required
                                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-sm text-on-surface font-semibold focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                placeholder="Contoh: Dewan Pembina & Pengawas Yayasan"
                            />
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                    Keterangan / Subtitle (Opsional)
                                </label>
                                <button
                                    v-if="levelForm.subtitle"
                                    type="button"
                                    @click="levelForm.subtitle = ''"
                                    class="text-[11px] font-semibold text-error hover:underline cursor-pointer"
                                >
                                    Hapus Keterangan
                                </button>
                            </div>
                            <textarea
                                v-model="levelForm.subtitle"
                                rows="2"
                                class="w-full px-4 py-2 bg-surface-container-low border border-outline-variant/60 rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all resize-none"
                                placeholder="Kosongkan jika tidak membutuhkan keterangan pada baris ini"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-outline-variant/30">
                            <button
                                type="button"
                                @click="showLevelEditModal = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:bg-surface-container-high border border-outline-variant/50 transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="levelForm.processing"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-bold text-xs transition-all shadow-xs cursor-pointer disabled:opacity-60"
                            >
                                <span v-if="levelForm.processing" class="material-symbols-outlined text-[15px] animate-spin">sync</span>
                                <span v-else class="material-symbols-outlined text-[15px]">check</span>
                                <span>{{ levelForm.processing ? 'Menyimpan...' : 'Simpan Nama' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
