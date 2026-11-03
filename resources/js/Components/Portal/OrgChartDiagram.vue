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

function setZoom(val) {
    isAutoFit.value = false;
    zoomScale.value = Math.min(1.2, Math.max(0.5, Number(val.toFixed(2))));
    nextTick(updateConnectionLines);
}

function toggleAutoFit() {
    isAutoFit.value = true;
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

// ========================================================
// GARIS HIERARKI SVG DINAMIS (ANTAR ATASAN DAN BAWAHAN / SEJAJAR)
// ========================================================
function updateConnectionLines() {
    if (!diagramContainerRef.value || !tableRef.value || !canvasWrapperRef.value) return;

    const containerEl = diagramContainerRef.value;
    const wrapperEl = canvasWrapperRef.value;
    const tableEl = tableRef.value;

    // Hitung ukuran asli tabel (unscaled) dan sesuaikan Auto-Fit jika aktif
    const rawTableWidth = tableEl.offsetWidth || 600;
    const rawTableHeight = tableEl.offsetHeight || 400;
    const availableWidth = containerEl.clientWidth || rawTableWidth;

    if (isAutoFit.value) {
        if (rawTableWidth > availableWidth && availableWidth > 240) {
            const fitRatio = Math.max(0.55, Math.min(1, (availableWidth - 8) / rawTableWidth));
            zoomScale.value = Number(fitRatio.toFixed(2));
        } else {
            zoomScale.value = 1;
        }
    }

    const wrapperRect = wrapperEl.getBoundingClientRect();
    const scale = (wrapperEl.offsetWidth > 0 ? wrapperRect.width / wrapperEl.offsetWidth : zoomScale.value) || 1;

    // Helper konversi koordinat layar ke koordinat internal canvas (unscaled)
    const toX = (screenX) => Math.round((screenX - wrapperRect.left) / scale);
    const toY = (screenY) => Math.round((screenY - wrapperRect.top) / scale);

    // Cari koordinat paling kanan dari seluruh kartu untuk jalur koridor kanan (garis lompat level)
    let maxCardRight = 0;
    props.positions.forEach(pos => {
        const el = document.getElementById(`pos-card-${pos.id}`);
        if (el) {
            const r = el.getBoundingClientRect();
            const rightCoord = toX(r.right);
            if (rightCoord > maxCardRight) {
                maxCardRight = rightCoord;
            }
        }
    });

    svgWidth.value = Math.max(rawTableWidth, maxCardRight + 48);
    svgHeight.value = rawTableHeight;

    const lines = [];
    const dotsMap = new Map();
    let jumpCount = 0;

    // Kumpulkan seluruh bounding box kartu untuk deteksi rintangan / penghalang (obstacle avoidance)
    const allCardRects = [];
    props.positions.forEach(p => {
        const el = document.getElementById(`pos-card-${p.id}`);
        if (el) {
            const r = el.getBoundingClientRect();
            allCardRects.push({
                id: p.id,
                name: p.name,
                level: Number(p.level),
                parent_id: p.parent_id,
                left: toX(r.left),
                right: toX(r.right),
                top: toY(r.top),
                bottom: toY(r.bottom),
                centerX: toX(r.left + r.width / 2),
                centerY: toY(r.top + r.height / 2),
            });
        }
    });

    function findObstacleCard(x, yA, yB, ignoreIds = []) {
        const minY = Math.min(yA, yB);
        const maxY = Math.max(yA, yB);
        return allCardRects.find(c => {
            if (ignoreIds.includes(c.id)) return false;
            const inY = c.top < maxY - 5 && c.bottom > minY + 5;
            if (!inY) return false;
            return x >= c.left - 5 && x <= c.right + 5;
        });
    }

    function computeDetourX(obstacle, targetX, parentId, childId) {
        const rightNeighbor = allCardRects.find(o =>
            o.id !== obstacle.id &&
            o.id !== parentId &&
            o.id !== childId &&
            Math.abs(o.top - obstacle.top) < 30 &&
            o.left >= obstacle.right - 8
        );
        const leftNeighbor = allCardRects.find(o =>
            o.id !== obstacle.id &&
            o.id !== parentId &&
            o.id !== childId &&
            Math.abs(o.top - obstacle.top) < 30 &&
            o.right <= obstacle.left + 8
        );

        if (targetX < obstacle.centerX - 8 && leftNeighbor) {
            return Math.round((leftNeighbor.right + obstacle.left) / 2);
        } else if (rightNeighbor) {
            return Math.round((obstacle.right + rightNeighbor.left) / 2);
        } else if (leftNeighbor) {
            return Math.round((leftNeighbor.right + obstacle.left) / 2);
        }
        return targetX < obstacle.centerX ? Math.round(obstacle.left - 16) : Math.round(obstacle.right + 16);
    }

    // Hitung bawahan langsung di level bawahnya untuk percabangan T-junction
    const directChildrenByParent = new Map();
    props.positions.forEach(pos => {
        if (!pos.parent_id) return;
        const parent = positionsMap.value.get(pos.parent_id);
        if (parent && Number(pos.level) === Number(parent.level) + 1) {
            directChildrenByParent.set(pos.parent_id, (directChildrenByParent.get(pos.parent_id) || 0) + 1);
        }
    });

    // Loop through all positions that have a parent
    props.positions.forEach(pos => {
        if (!pos.parent_id) return;

        const childEl = document.getElementById(`pos-card-${pos.id}`);
        const parentEl = document.getElementById(`pos-card-${pos.parent_id}`);

        if (childEl && parentEl) {
            const childRect = childEl.getBoundingClientRect();
            const parentRect = parentEl.getBoundingClientRect();

            const parentLevel = Number(parentEl.dataset.level) || 1;
            const childLevel = Number(childEl.dataset.level) || 1;
            const parentLevelDef = levelDefinitions.find(d => d.level === parentLevel) || levelDefinitions[0];

            let path = '';
            let isPeer = false;

            // 1. KASUS SATU LEVEL (childLevel === parentLevel)
            if (childLevel === parentLevel) {
                if (toY(childRect.top) > toY(parentRect.bottom) - 8) {
                    // ATAS-BAWAH DI DALAM 1 CONTAINER LEVEL
                    const x1 = toX(parentRect.left + parentRect.width / 2);
                    const y1 = toY(parentRect.bottom);
                    const x2 = toX(childRect.left + childRect.width / 2);
                    const y2 = toY(childRect.top);

                    if (Math.abs(x1 - x2) < 4) {
                        path = `M ${x1} ${y1} L ${x2} ${y2}`;
                    } else {
                        const midY = Math.round(y1 + (y2 - y1) * 0.5);
                        path = `M ${x1} ${y1} L ${x1} ${midY} L ${x2} ${midY} L ${x2} ${y2}`;

                        const siblings = (intraChildrenMapByLevel.value[pos.parent_id] || []);
                        if (siblings.length > 1) {
                            const dotKey = `${pos.parent_id}-${midY}`;
                            if (!dotsMap.has(dotKey)) {
                                dotsMap.set(dotKey, {
                                    id: `dot-${dotKey}`,
                                    cx: x1,
                                    cy: midY,
                                    color: parentLevelDef.strokeColor,
                                });
                            }

                            const hasFurtherSibling = siblings.some(s => {
                                if (s.id === pos.id) return false;
                                const sEl = document.getElementById(`pos-card-${s.id}`);
                                if (!sEl) return false;
                                const sRect = sEl.getBoundingClientRect();
                                const sX = toX(sRect.left + sRect.width / 2);
                                return (x2 > x1 && sX > x2 + 8) || (x2 < x1 && sX < x2 - 8);
                            });
                            if (hasFurtherSibling) {
                                const branchDotKey = `branch-${pos.id}-${midY}`;
                                if (!dotsMap.has(branchDotKey)) {
                                    dotsMap.set(branchDotKey, {
                                        id: `dot-${branchDotKey}`,
                                        cx: x2,
                                        cy: midY,
                                        color: parentLevelDef.strokeColor,
                                    });
                                }
                            }
                        }
                    }

                    lines.push({
                        id: `line-${pos.parent_id}-${pos.id}`,
                        parentId: pos.parent_id,
                        childId: pos.id,
                        parentLevel: parentLevelDef.level,
                        x1,
                        y1,
                        x2,
                        y2,
                        path,
                        color: parentLevelDef.strokeColor,
                        isPeer: false,
                    });
                    return;
                } else {
                    // SEJAJAR HORIZONTAL DI LEVEL YANG SAMA
                    isPeer = true;
                    const parentIsLeft = parentRect.left < childRect.left;
                    const x1 = toX(parentIsLeft ? parentRect.right : parentRect.left);
                    const y1 = toY(parentRect.top + parentRect.height / 2);
                    const x2 = toX(parentIsLeft ? childRect.left : childRect.right);
                    const y2 = toY(childRect.top + childRect.height / 2);

                    if (Math.abs(y1 - y2) < 6) {
                        path = `M ${x1} ${y1} L ${x2} ${y2}`;
                    } else {
                        const midX = Math.round(x1 + (x2 - x1) * 0.5);
                        path = `M ${x1} ${y1} L ${midX} ${y1} L ${midX} ${y2} L ${x2} ${y2}`;
                    }

                    lines.push({
                        id: `line-${pos.parent_id}-${pos.id}`,
                        parentId: pos.parent_id,
                        childId: pos.id,
                        parentLevel: parentLevelDef.level,
                        path,
                        color: parentLevelDef.strokeColor,
                        isPeer: true,
                    });
                    return;
                }
            }

            // Coordinate from bottom center of parent card
            const x1 = toX(parentRect.left + parentRect.width / 2);
            const y1 = toY(parentRect.bottom);

            // Coordinate to top center of child card
            const x2 = toX(childRect.left + childRect.width / 2);
            const y2 = toY(childRect.top);

            // 2. KASUS LOMPAT LEVEL (childLevel > parentLevel + 1)
            if (childLevel > parentLevel + 1) {
                const rightCorridorBase = Math.max(maxCardRight + 24, rawTableWidth - 48);
                const x_right = Math.round(rightCorridorBase + (jumpCount * 12));
                jumpCount++;

                const y_exit = y1 + 12;
                const y_entry = y2 - 12;

                path = `M ${x1} ${y1} L ${x1} ${y_exit} L ${x_right} ${y_exit} L ${x_right} ${y_entry} L ${x2} ${y_entry} L ${x2} ${y2}`;
            }
            // 3. KASUS LEVEL DI BAWAHNYA LANGSUNG (childLevel === parentLevel + 1)
            else {
                const parentRowEl = document.getElementById(`level-row-${parentLevel}`);
                let midY;
                if (parentRowEl) {
                    const rowRect = parentRowEl.getBoundingClientRect();
                    midY = toY(rowRect.bottom);
                } else {
                    midY = Math.round(y1 + (y2 - y1) * 0.5);
                }

                // Pisahkan pengecekan rintangan di level atasan (y1..midY) dan di level bawahan (midY..y2)
                const obstacleInParent = findObstacleCard(x1, y1, midY, [pos.parent_id, pos.id]);
                const obstacleInChild = findObstacleCard(x2, midY, y2, [pos.parent_id, pos.id]);

                if (obstacleInParent) {
                    const x_detour = computeDetourX(obstacleInParent, x2, pos.parent_id, pos.id);
                    const y_above = Math.round(y1 + Math.max(8, (obstacleInParent.top - y1) * 0.5));
                    const y_below = Math.max(Math.round(obstacleInParent.bottom + 10), midY);
                    path = `M ${x1} ${y1} L ${x1} ${y_above} L ${x_detour} ${y_above} L ${x_detour} ${y_below} L ${x2} ${y_below} L ${x2} ${y2}`;
                } else if (obstacleInChild) {
                    // Belok di garis batas antar level (midY) agar tidak pernah memotong kartu di level atas maupun bawah
                    const x_detour = computeDetourX(obstacleInChild, x2, pos.parent_id, pos.id);
                    const y_below = Math.round(obstacleInChild.bottom + Math.max(8, (y2 - obstacleInChild.bottom) * 0.5));
                    path = `M ${x1} ${y1} L ${x1} ${midY} L ${x_detour} ${midY} L ${x_detour} ${y_below} L ${x2} ${y_below} L ${x2} ${y2}`;
                } else if (Math.abs(x1 - x2) <= 6) {
                    path = `M ${x1} ${y1} L ${x1} ${y2}`;
                } else {
                    path = `M ${x1} ${y1} L ${x1} ${midY} L ${x2} ${midY} L ${x2} ${y2}`;
                }

                // Junction dot pada titik percabangan horizontal di batas level
                if (!obstacleInParent && (directChildrenByParent.get(pos.parent_id) || 0) > 1) {
                    const dotKey = `${pos.parent_id}-${midY}`;
                    if (!dotsMap.has(dotKey)) {
                        dotsMap.set(dotKey, {
                            id: `dot-${dotKey}`,
                            cx: x1,
                            cy: midY,
                            color: parentLevelDef.strokeColor,
                        });
                    }
                }
            }

            lines.push({
                id: `line-${pos.parent_id}-${pos.id}`,
                parentId: pos.parent_id,
                childId: pos.id,
                parentLevel: parentLevelDef.level,
                x1,
                y1,
                x2,
                y2,
                path,
                color: parentLevelDef.strokeColor,
                isPeer: false,
            });
        }
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

let resizeObserver = null;
onMounted(() => {
    nextTick(() => {
        updateConnectionLines();
        setTimeout(updateConnectionLines, 150);
    });

    window.addEventListener('resize', updateConnectionLines);

    if (window.ResizeObserver && diagramContainerRef.value) {
        resizeObserver = new ResizeObserver(() => {
            updateConnectionLines();
        });
        resizeObserver.observe(diagramContainerRef.value);
    }
});

onUnmounted(() => {
    if (resizeObserver) resizeObserver.disconnect();
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

                    <!-- Top Action Buttons -->
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <button
                            type="button"
                            @click="$emit('add-department')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-outline-variant/60 hover:bg-surface-container-high text-on-surface font-semibold text-xs transition-all cursor-pointer"
                            title="Tambah bidang kerja yayasan"
                        >
                            <span class="material-symbols-outlined text-[16px]">add_business</span>
                            <span>Tambah Bidang</span>
                        </button>
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
                                class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                                :class="activeTab === 'diagram'
                                    ? 'bg-primary text-on-primary shadow-xs'
                                    : 'text-on-surface-variant hover:text-on-surface'"
                            >
                                <span class="material-symbols-outlined text-[16px]">view_agenda</span>
                                <span>Diagram Hierarki</span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'departments'"
                                class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                                :class="activeTab === 'departments'
                                    ? 'bg-primary text-on-primary shadow-xs'
                                    : 'text-on-surface-variant hover:text-on-surface'"
                            >
                                <span class="material-symbols-outlined text-[16px]">domain</span>
                                <span>Daftar Unit</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TABEL 5 BARIS LEVEL MENYATU DENGAN GARIS HIERARKI DINAMIS -->
            <!-- ======================================================== -->
            <div
                v-if="activeTab === 'diagram'"
                ref="diagramContainerRef"
                class="relative overflow-x-auto overflow-y-hidden w-full"
                @scroll="updateConnectionLines"
            >
                <div
                    :style="zoomScale !== 1 && svgHeight > 0
                        ? { width: `${Math.ceil(svgWidth * zoomScale)}px`, height: `${Math.ceil(svgHeight * zoomScale) + 4}px`, minWidth: '100%' }
                        : { minWidth: '100%' }"
                >
                <div
                    ref="canvasWrapperRef"
                    class="relative w-max min-w-full origin-top-left transition-transform duration-150"
                    :style="zoomScale !== 1 ? { transform: `scale(${zoomScale})` } : {}"
                >
                <!-- KANVAS SVG GARIS HIERARKI PENGHUBUNG ANTARA ATASAN DAN BAWAHAN -->
                <svg
                    class="absolute inset-0 pointer-events-none z-10 overflow-visible transition-all"
                    :style="{
                        width: `${svgWidth}px`,
                        height: `${svgHeight}px`,
                        minWidth: '100%',
                        minHeight: '100%',
                    }"
                >
                    <defs>
                        <!-- Marker Arrowhead untuk masing-masing level warna -->
                        <marker
                            v-for="d in levelDefinitions"
                            :key="`arrow-${d.level}`"
                            :id="`arrow-${d.level}`"
                            viewBox="0 0 10 10"
                            refX="7"
                            refY="5"
                            markerWidth="5.5"
                            markerHeight="5.5"
                            orient="auto-start-reverse"
                        >
                            <path d="M 0 1.5 L 8 5 L 0 8.5 z" :fill="d.strokeColor" />
                        </marker>
                    </defs>

                    <!-- Render Setiap Garis Penghubung (Parent -> Child / Peer) bersudut 90 derajat -->
                    <g v-for="line in connectionLines" :key="line.id">
                        <!-- Glow / Shadow Line saat di-hover -->
                        <path
                            :d="line.path"
                            stroke="rgba(5, 150, 105, 0.2)"
                            :stroke-width="hoveredPosId === line.parentId || hoveredPosId === line.childId ? 6 : 3"
                            fill="none"
                            stroke-linejoin="miter"
                            class="transition-all duration-150"
                        />
                        <!-- Garis Utama Penghubung (Bersudut 90 Derajat / Sejajar Horizontal) -->
                        <path
                            :d="line.path"
                            :stroke="hoveredPosId === line.parentId || hoveredPosId === line.childId ? '#059669' : line.color"
                            :stroke-width="hoveredPosId === line.parentId || hoveredPosId === line.childId ? 2.5 : 1.75"
                            :stroke-opacity="hoveredPosId && hoveredPosId !== line.parentId && hoveredPosId !== line.childId ? 0.25 : 0.9"
                            fill="none"
                            stroke-linejoin="miter"
                            :marker-end="line.isPeer ? '' : `url(#arrow-${line.parentLevel || 1})`"
                            class="transition-all duration-150"
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
                            <td class="px-2.5 pt-2 pb-2.5 sm:px-4 sm:pt-2.5 sm:pb-3 align-top relative" :class="def.rowHeaderBg">
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
                                    <!-- Tombol Edit Ringkas -->
                                    <button
                                        type="button"
                                        @click="openEditLevelModal(def.level)"
                                        class="inline-flex items-center p-0.5 text-slate-500 hover:text-primary rounded hover:bg-slate-100 transition-colors cursor-pointer"
                                        :title="`Edit Nama Level ${def.level}`"
                                    >
                                        <span class="material-symbols-outlined text-[12px] sm:text-[13px]">edit</span>
                                    </button>
                                    <!-- Tombol Tambah Ringkas -->
                                    <button
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
                                    class="flex items-start justify-center gap-3.5 sm:gap-5 flex-nowrap py-1 w-max min-w-full mx-auto"
                                >
                                    <OrgPositionNode
                                        v-for="rootPos in intraRootsByLevel[def.level]"
                                        :key="rootPos.id"
                                        :pos="rootPos"
                                        :level-def="def"
                                        :intra-children-map="intraChildrenMapByLevel"
                                        :hovered-pos-id="hoveredPosId"
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
            </div>

            <!-- Baris Tambah Level Selanjutnya (Jika belum sampai Level 5) -->
            <div
                v-if="activeTab === 'diagram' && maxActiveLevel < 5"
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
            <div v-else-if="activeTab === 'departments'" class="p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-base font-bold text-on-surface">Daftar Unit</h4>
                        <p class="text-xs text-on-surface-variant">Kelola nama unit dan grup posisi di bawah yayasan</p>
                    </div>
                    <button
                        type="button"
                        @click="$emit('add-department')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary text-on-primary text-xs sm:text-sm font-semibold cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        <span>Tambah Unit</span>
                    </button>
                </div>

                <div class="overflow-x-auto border border-outline-variant/60 rounded-xl">
                    <table class="w-full text-xs sm:text-sm border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low/70 text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                <th class="border-b border-outline-variant/60 px-5 py-3 text-left w-1/4">Unit</th>
                                <th class="border-b border-outline-variant/60 px-4 py-3 text-left w-48">Kelompok Unit</th>
                                <th class="border-b border-outline-variant/60 px-5 py-3 text-left">Jabatan Terdaftar di Unit</th>
                                <th class="border-b border-outline-variant/60 px-5 py-3 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="dept in departments"
                                :key="dept.id"
                                class="border-b border-outline-variant/40 hover:bg-surface-container-low/30 transition-colors"
                            >
                                <td class="px-5 py-3.5 align-top font-bold text-on-surface">
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
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-surface-container-high border border-outline-variant/40"
                                        >
                                            <span class="text-[9px] font-bold px-1 rounded bg-primary/10 text-primary">
                                                L{{ p.level || 3 }}
                                            </span>
                                            <span>{{ p.name }}</span>
                                        </span>
                                        <span
                                            v-if="!positions.some(x => x.department_id === dept.id)"
                                            class="text-xs text-on-surface-variant italic"
                                        >
                                            Belum ada jabatan di unit ini
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 align-top text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="$emit('edit-department', dept)"
                                            class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer"
                                            title="Edit Unit"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="$emit('delete-department', dept)"
                                            class="p-1.5 rounded-lg text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors cursor-pointer"
                                            title="Hapus Unit"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="departments.length === 0">
                                <td colspan="4" class="px-5 py-8 text-center text-on-surface-variant">
                                    Belum ada unit terdaftar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
