<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BriefcaseBusiness,
    CalendarDays,
    CircleHelp,
    Clock3,
    FolderKanban,
    ListChecks,
    MoreHorizontal,
    Plus,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/Components/UI/button';
import { useProjectContext } from '@/composables/useProjectContext';
import { useAuthStore } from '@/stores/useAuthStore';

type StatCard = {
    label: string;
    value: number;
    helper: string;
};

type ChartItem = {
    label: string;
    value: number;
};

const props = defineProps<{
    stats: StatCard[];
    ticketChart: ChartItem[];
    roleChart: ChartItem[];
    canViewAnalytics: boolean;
    canManageRoles: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'For you',
                href: route('dashboard'),
            },
        ],
    },
});

const authStore = useAuthStore();
const { context } = useProjectContext();

const firstName = computed(
    () => authStore.user?.name?.split(' ')[0] ?? 'there',
);
const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 18) {
        return 'Good afternoon';
    }

    return 'Good evening';
});
const stat = (label: string) =>
    props.stats.find((item) => item.label === label)?.value ?? 0;
const workspaceCount = computed(() => context.value?.workspaces.length ?? 0);
const boardCount = computed(() =>
    context.value?.workspaces.reduce(
        (total, workspace) => total + workspace.boards.length,
        0,
    ) ?? 0,
);
const visibleWorkspaces = computed(() =>
    (context.value?.workspaces ?? []).slice(0, 5),
);
const maxTicketValue = computed(() =>
    Math.max(1, ...props.ticketChart.map((item) => item.value)),
);
const maxRoleValue = computed(() =>
    Math.max(1, ...props.roleChart.map((item) => item.value)),
);
const totalPeople = computed(() =>
    props.roleChart.reduce((total, item) => total + item.value, 0),
);
const openTaskCount = computed(
    () =>
        props.ticketChart.find((item) =>
            item.label.toLowerCase().includes('open'),
        )?.value ?? props.ticketChart[0]?.value ?? 0,
);
const completedTaskCount = computed(
    () =>
        props.ticketChart.find((item) =>
            item.label.toLowerCase().includes('completed'),
        )?.value ?? 0,
);
const onTrackPercent = computed(() => {
    const total = openTaskCount.value + completedTaskCount.value;

    return total ? Math.round((completedTaskCount.value / total) * 100) : 0;
});

const ticketTone = (label: string) => {
    const normalized = label.toLowerCase();

    if (normalized.includes('completed')) {
        return 'bg-[#2eaa7d]';
    }

    if (normalized.includes('archived')) {
        return 'bg-[#9ca3af]';
    }

    return 'bg-[#3b82f6]';
};

const ticketDotTone = (label: string) => {
    const normalized = label.toLowerCase();

    if (normalized.includes('completed')) {
        return 'bg-[#2eaa7d]';
    }

    if (normalized.includes('archived')) {
        return 'bg-[#9ca3af]';
    }

    return 'bg-[#f59e0b]';
};

const workspaceColor = (color: string | null, index: number) =>
    color ?? ['#f0b429', '#5b67d8', '#2eaa7d', '#ef6f6c', '#7c5ce6'][index % 5];
</script>

<template>
    <Head title="For you" />

    <section class="riraa-dashboard min-h-full flex-1 px-4 py-6 sm:px-8 sm:py-8 lg:px-12">
        <div class="mx-auto max-w-[1420px]">
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-start">
                <div>
                    <p class="riraa-eyebrow">LAUNCH CAMPAIGN</p>
                    <h1 class="mt-5 text-[2rem] font-semibold tracking-[-0.055em] sm:text-[2.5rem]">
                        Project overview
                    </h1>
                    <p class="mt-2 max-w-xl text-sm text-[#a39da9]">
                        {{ greeting }}, {{ firstName }}. Here’s what’s happening across
                        {{ authStore.user?.company?.name ?? 'your company' }}.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Button variant="outline" size="sm" class="riraa-share-button h-10 rounded-xl px-4 text-xs shadow-none">
                        Share
                    </Button>
                    <Button size="sm" class="riraa-add-task-button h-10 rounded-xl px-4 text-xs shadow-none">
                        <Plus class="size-4" /> Add task
                    </Button>
                </div>
            </div>

            <div class="mt-10 grid gap-4 lg:grid-cols-3">
                <article class="riraa-metric-card">
                    <p>On track</p>
                    <strong>{{ onTrackPercent }}%</strong>
                    <div class="riraa-progress-track"><div class="riraa-progress-fill riraa-progress-fill--teal" :style="{ width: `${Math.max(8, onTrackPercent)}%` }" /></div>
                </article>
                <article class="riraa-metric-card">
                    <p>Open tasks</p>
                    <strong>{{ openTaskCount }}</strong>
                    <div class="riraa-progress-track"><div class="riraa-progress-fill riraa-progress-fill--rose" :style="{ width: `${Math.max(16, Math.min(100, openTaskCount * 8))}%` }" /></div>
                </article>
                <article class="riraa-metric-card">
                    <p>Due this week</p>
                    <strong>{{ Math.max(0, openTaskCount - completedTaskCount) }}</strong>
                    <div class="riraa-progress-track"><div class="riraa-progress-fill riraa-progress-fill--lavender" :style="{ width: `${Math.max(12, Math.min(100, (openTaskCount + 1) * 10))}%` }" /></div>
                </article>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(340px,0.85fr)]">
                <article class="riraa-dashboard-card overflow-hidden">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-[#eceef1] px-5 py-5 sm:px-7">
                        <div>
                            <div class="flex items-center gap-2"><h2 class="text-lg font-semibold tracking-[-0.03em] text-[#252930]">My work</h2><span class="rounded bg-[#eef2f7] px-1.5 py-0.5 text-[10px] font-semibold text-[#77808c]">{{ stat('Tickets / cards') }}</span></div>
                            <p class="mt-1 text-xs text-[#8b919a]">A quick view of the work moving through your company.</p>
                        </div>
                        <Button as-child variant="ghost" size="sm" class="riraa-panel-action h-8 text-xs shadow-none"><Link :href="route('cards.index')">View all tickets <ArrowRight class="size-3.5" /></Link></Button>
                    </div>
                    <div class="flex gap-5 border-b border-[#eceef1] px-5 sm:px-7"><button type="button" class="riraa-tab riraa-tab--active">Overview</button><button type="button" class="riraa-tab">Open <span>{{ ticketChart[0]?.value ?? 0 }}</span></button><button type="button" class="riraa-tab">Completed <span>{{ ticketChart[1]?.value ?? 0 }}</span></button></div>
                    <div class="divide-y divide-[#f0f1f3]">
                        <Link :href="route('cards.index')" class="riraa-work-row"><span class="riraa-status-dot bg-[#f3b33d]"><Clock3 class="size-3" /></span><span class="min-w-0 flex-1"><strong>Open tickets across your boards</strong><small>Review active work and move the next item forward</small></span><span class="riraa-row-pill bg-[#fff3d6] text-[#9f6b0a]">{{ ticketChart[0]?.value ?? 0 }} open</span><ArrowRight class="hidden size-4 text-[#a8afb8] sm:block" /></Link>
                        <Link :href="route('boards.index')" class="riraa-work-row"><span class="riraa-status-dot bg-[#dfe8ff] text-[#3b72d9]"><BriefcaseBusiness class="size-3" /></span><span class="min-w-0 flex-1"><strong>Keep your project boards current</strong><small>{{ workspaceCount }} workspaces and {{ boardCount }} boards are available to your team</small></span><span class="riraa-row-pill bg-[#edf2ff] text-[#3b72d9]">{{ boardCount }} boards</span><ArrowRight class="hidden size-4 text-[#a8afb8] sm:block" /></Link>
                        <Link :href="route('users.index')" class="riraa-work-row"><span class="riraa-status-dot bg-[#dff5ec] text-[#2eaa7d]"><Users class="size-3" /></span><span class="min-w-0 flex-1"><strong>Keep the right people in the loop</strong><small>{{ totalPeople || stat('Users') }} people are represented in your company workspace</small></span><span class="riraa-row-pill bg-[#e5f7ef] text-[#278761]">{{ totalPeople || stat('Users') }} people</span><ArrowRight class="hidden size-4 text-[#a8afb8] sm:block" /></Link>
                    </div>
                    <div class="riraa-dashboard-footer flex items-center justify-between bg-[#fafbfc] px-5 py-3.5 text-xs text-[#7c838d] sm:px-7"><span>Last updated just now</span><span class="inline-flex items-center gap-1.5"><CircleHelp class="size-3.5" /> Need a hand?</span></div>
                </article>

                <article class="riraa-dashboard-card overflow-hidden">
                    <div class="flex items-start justify-between border-b border-[#eceef1] px-5 py-5"><div><h2 class="text-lg font-semibold tracking-[-0.03em] text-[#252930]">Projects</h2><p class="mt-1 text-xs text-[#8b919a]">Recent workspaces and boards.</p></div><Button v-if="canManageRoles || context?.workspaces.length" as-child variant="ghost" size="icon-sm" class="size-8 rounded-lg text-[#777f89] hover:bg-[#f3f4f6]" title="View projects"><Link :href="route('boards.index')"><MoreHorizontal class="size-4" /></Link></Button></div>
                    <div v-if="visibleWorkspaces.length" class="divide-y divide-[#f0f1f3]">
                        <Link v-for="(workspace, index) in visibleWorkspaces" :key="workspace.id" :href="route('boards.index', { workspace_id: workspace.id })" class="riraa-project-row"><span class="flex size-8 shrink-0 items-center justify-center rounded-lg text-white" :style="{ backgroundColor: workspaceColor(workspace.color, index) }"><FolderKanban class="size-4" /></span><span class="min-w-0 flex-1"><strong>{{ workspace.name }}</strong><small>{{ workspace.boards_count }} {{ workspace.boards_count === 1 ? 'board' : 'boards' }}</small></span><span class="riraa-project-arrow"><ArrowRight class="size-3.5" /></span></Link>
                    </div>
                    <div v-else class="px-5 py-10 text-center"><FolderKanban class="mx-auto size-7 text-[#c1c6ce]" /><p class="mt-3 text-sm font-medium text-[#5c6470]">No workspaces yet</p><p class="mt-1 text-xs text-[#8b919a]">Create one to give your team a home for work.</p></div>
                    <div class="border-t border-[#eceef1] px-5 py-3.5"><Link :href="route('workspaces.index')" class="riraa-panel-action inline-flex text-xs">View all workspaces <ArrowRight class="size-3.5" /></Link></div>
                </article>
            </div>

            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <article class="riraa-dashboard-card p-5 sm:p-7">
                    <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-semibold tracking-[-0.03em] text-[#252930]">Work status</h2><p class="mt-1 text-xs text-[#8b919a]">Where tickets sit across the company.</p></div><span class="riraa-panel-icon bg-[#edf2ff] text-[#3b72d9]"><ListChecks class="size-4" /></span></div>
                    <div v-if="ticketChart.length" class="mt-7 space-y-5"><div v-for="item in ticketChart" :key="item.label"><div class="mb-2 flex items-center justify-between text-xs"><span class="flex items-center gap-2 font-medium text-[#4e5661]"><span class="size-2 rounded-full" :class="ticketDotTone(item.label)" />{{ item.label }}</span><strong class="text-[#30363e]">{{ item.value }}</strong></div><div class="h-2 rounded-full bg-[#eef0f3]"><div class="h-2 rounded-full transition-all" :class="ticketTone(item.label)" :style="{ width: `${Math.max(4, (item.value / maxTicketValue) * 100)}%` }" /></div></div></div>
                    <div v-else class="riraa-empty-state mt-7 rounded-lg bg-[#fafbfc] p-5 text-sm text-[#808792]">Analytics will appear here as your team creates and moves tickets.</div>
                </article>

                <article class="riraa-dashboard-card p-5 sm:p-7">
                    <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-semibold tracking-[-0.03em] text-[#252930]">People and access</h2><p class="mt-1 text-xs text-[#8b919a]">A snapshot of your company membership.</p></div><span class="riraa-panel-icon bg-[#e5f7ef] text-[#2eaa7d]"><Users class="size-4" /></span></div>
                    <div v-if="roleChart.length" class="mt-7 flex items-end gap-3 sm:gap-5"><div v-for="item in roleChart" :key="item.label" class="flex min-w-0 flex-1 flex-col items-center gap-2"><div class="flex h-32 w-full items-end rounded-lg bg-[#f5f6f8] px-2" :title="`${item.value} ${item.label}`"><div class="w-full rounded-md bg-[#86baf9]" :style="{ height: `${Math.max(18, (item.value / maxRoleValue) * 100)}%` }"><span class="flex -translate-y-6 justify-center text-xs font-semibold text-[#4b5563]">{{ item.value }}</span></div></div><span class="max-w-full truncate text-[11px] text-[#737b86]">{{ item.label }}</span></div></div>
                    <div v-else class="riraa-empty-state mt-7 rounded-lg bg-[#fafbfc] p-5 text-sm text-[#808792]">People insights will appear when members join your company.</div>
                    <div class="mt-6 flex items-center justify-between border-t border-[#eef0f2] pt-4 text-xs text-[#7d848e]"><span>{{ totalPeople || stat('Users') }} total members</span><Link :href="route('users.index')" class="riraa-panel-action inline-flex text-xs">Manage people</Link></div>
                </article>
            </div>

            <div class="riraa-dashboard-prompt mt-5 flex flex-col gap-3 rounded-xl border border-[#e5e7eb] bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7"><div class="flex items-center gap-3"><span class="flex size-9 items-center justify-center rounded-lg bg-[#eef2ff] text-[#3b72d9]"><CalendarDays class="size-4" /></span><div><p class="riraa-dashboard-prompt-title text-sm font-semibold text-[#3f4650]">Keep your company workspace organized</p><p class="riraa-dashboard-prompt-description mt-0.5 text-xs text-[#858c96]">Set up the next workspace, project board, or role rule when you’re ready.</p></div></div><div class="flex flex-wrap gap-2"><Link :href="route('workspaces.index')" class="riraa-landing-pill riraa-landing-pill--dark"><span class="riraa-pill-icon riraa-pill-icon--lavender"><Plus class="size-3.5" /></span> Workspace</Link><Link :href="route('boards.index')" class="riraa-landing-pill riraa-landing-pill--dark"><span class="riraa-pill-icon riraa-pill-icon--rose"><Plus class="size-3.5" /></span> Project board</Link></div></div>
        </div>
    </section>
</template>

<style scoped>
:global(html.dark:has(.riraa-dashboard)),
:global(html.dark body:has(.riraa-dashboard)) {
    background: #17151c !important;
}

:global(html.dark body:has(.riraa-dashboard) [data-sidebar='sidebar']) {
    border-color: #3b3742 !important;
    background: #201e25 !important;
    color: #f5f1f5 !important;
}

:global(html.dark body:has(.riraa-dashboard) [data-sidebar='content']),
:global(html.dark body:has(.riraa-dashboard) [data-sidebar='header']),
:global(html.dark body:has(.riraa-dashboard) [data-sidebar='footer']) {
    background: #201e25 !important;
}

:global(html.dark body:has(.riraa-dashboard) [data-sidebar='separator']) {
    background: #3b3742 !important;
}

:global(html.dark body:has(.riraa-dashboard) [data-sidebar='group-label']) {
    color: #847d8b !important;
}

:global(html.dark body:has(.riraa-dashboard) [data-sidebar='menu-button']) {
    color: #aaa3ad !important;
}

:global(html.dark body:has(.riraa-dashboard) [data-sidebar='menu-button']:hover) {
    background: #2b2831 !important;
    color: #fff !important;
}

:global(html.dark body:has(.riraa-dashboard) [data-sidebar='menu-button'][data-size='lg']),
:global(html.dark body:has(.riraa-dashboard) [data-sidebar='menu-button'][data-size='lg'][data-state='open']) {
    background: #201e25 !important;
    color: #f5f1f5 !important;
}

:global(html.dark body:has(.riraa-dashboard) [data-sidebar='menu-button'][data-active='true']),
:global(html.dark body:has(.riraa-dashboard) [data-sidebar='menu-sub-button'][data-active='true']) {
    background: #393640 !important;
    color: #fff !important;
}

:global(html.dark body:has(.riraa-dashboard) [data-sidebar='menu-sub-button']) {
    color: #9b94a1 !important;
}

:global(html.dark body:has(.riraa-dashboard) [data-sidebar='menu-sub-button']:hover),
:global(html.dark body:has(.riraa-dashboard) [data-sidebar='menu-sub-button'][data-active='true']) {
    background: #2b2831 !important;
    color: #fff !important;
}

:global(html.dark body:has(.riraa-dashboard) .riraa-app-header) {
    border-color: #3b3742 !important;
    background: #17151c !important;
    color: #f5f1f5 !important;
}

:global(html.dark body:has(.riraa-dashboard) .riraa-app-header .riraa-search-link) {
    border-color: #3b3742 !important;
    background: #25232b !important;
    color: #918a97 !important;
}

:global(html.dark body:has(.riraa-dashboard) .riraa-app-header .riraa-search-link:hover) {
    border-color: #635b6b !important;
    background: #2b2831 !important;
    color: #fff !important;
}

:global(html.dark body:has(.riraa-dashboard) .riraa-app-header [data-slot='breadcrumb-page']) {
    color: #f5f1f5 !important;
}

:global(html.dark body:has(.riraa-dashboard) .riraa-app-header .riraa-top-context [class~='text-[#30343b]']) {
    color: #aaa3ad !important;
}

:global(html.dark body:has(.riraa-dashboard) .riraa-app-header [data-slot='breadcrumb-separator']),
:global(html.dark body:has(.riraa-dashboard) .riraa-app-header [data-sidebar='trigger']) {
    color: #8f8895 !important;
}

:global(html.dark body:has(.riraa-dashboard) .riraa-app-header [data-sidebar='trigger']:hover),
:global(html.dark body:has(.riraa-dashboard) .riraa-app-header button:hover) {
    background: #2b2831 !important;
    color: #fff !important;
}

.riraa-dashboard {
    font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
    background: #17151c;
    color: #f5f1f5;
}

.riraa-dashboard h1 {
    background-image: none;
    color: #fff !important;
    -webkit-text-fill-color: #fff;
}

.riraa-dashboard h2 {
    color: #f5f1f5;
}

.riraa-eyebrow {
    color: #817a89;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.2em;
}

.riraa-share-button {
    border-color: #46404d !important;
    background: transparent !important;
    color: #aaa3ad !important;
}

.riraa-share-button:hover {
    border-color: #655d6d !important;
    background: #2b2831 !important;
    color: #fff !important;
}

.riraa-add-task-button {
    background: #f04b67 !important;
    color: #fff !important;
}

.riraa-add-task-button:hover {
    background: #db3856 !important;
}

.riraa-metric-card,
.riraa-dashboard-card {
    border: 1px solid #423d47;
    border-radius: 1rem;
    background: #2a2830;
    box-shadow: none;
}

.riraa-metric-card {
    min-height: 9.2rem;
    padding: 1.55rem 1.65rem;
}

.riraa-metric-card p {
    color: #9b94a1;
    font-size: 0.8rem;
}

.riraa-metric-card strong {
    display: block;
    margin-top: 1rem;
    color: #fff;
    font-size: 2.25rem;
    font-weight: 600;
    letter-spacing: -0.06em;
    line-height: 1;
}

.riraa-progress-track {
    height: 0.45rem;
    margin-top: 1.2rem;
    overflow: hidden;
    border-radius: 999px;
    background: #45414b;
}

.riraa-progress-fill {
    height: 100%;
    border-radius: inherit;
}

.riraa-progress-fill--teal { background: #9ee0d4; }
.riraa-progress-fill--rose { background: #f6b5c5; }
.riraa-progress-fill--lavender { background: #c5b4f3; }

.riraa-dashboard-card {
    overflow: hidden;
}

.riraa-dashboard-card > div.border-b,
.riraa-dashboard-card > div.border-t,
.riraa-dashboard-card .border-t {
    border-color: #3d3844 !important;
}

.riraa-dashboard-card .divide-y > * {
    border-color: #36313b !important;
}

.riraa-dashboard-card [class~='bg-[#fafbfc]'] {
    background: #211f26 !important;
}

.riraa-panel-action {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border: 1px solid #46404d;
    border-radius: 0.75rem;
    background: transparent;
    color: #aaa3ad;
    font-size: 0.75rem;
    font-weight: 600;
    transition: border-color 160ms ease, background-color 160ms ease, color 160ms ease;
}

.riraa-panel-action:hover {
    border-color: #6b6272;
    background: #34303a;
    color: #fff;
}

.riraa-tab {
    position: relative;
    padding: 0.8rem 0.05rem;
    color: #817a89;
    font-size: 0.72rem;
    font-weight: 600;
}

.riraa-tab span { margin-left: 0.25rem; color: #9a93a1; font-weight: 500; }
.riraa-tab--active { color: #fff; }
.riraa-tab--active::after { position: absolute; bottom: -1px; left: 0; right: 0; height: 2px; border-radius: 99px; background: #f04b67; content: ''; }

.riraa-work-row,
.riraa-project-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: background-color 160ms ease;
}

.riraa-work-row { padding: 1rem 1.25rem; }
.riraa-project-row { padding: 0.85rem 1.25rem; }
.riraa-work-row:hover,
.riraa-project-row:hover { background: #302c36; }

.riraa-work-row strong,
.riraa-project-row strong { display: block; overflow: hidden; color: #d8d2dc; font-size: 0.78rem; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
.riraa-work-row small,
.riraa-project-row small { display: block; margin-top: 0.25rem; overflow: hidden; color: #8e8795; font-size: 0.68rem; text-overflow: ellipsis; white-space: nowrap; }
.riraa-status-dot { display: flex; height: 1.8rem; width: 1.8rem; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 999px; color: #f6b35e; }
.riraa-row-pill { flex-shrink: 0; border-radius: 999px; padding: 0.3rem 0.55rem; font-size: 0.62rem; font-weight: 600; }
.riraa-project-arrow { display: flex; color: #8f8895; opacity: 0; transition: opacity 160ms ease, transform 160ms ease; }
.riraa-project-row:hover .riraa-project-arrow { opacity: 1; transform: translateX(2px); }
.riraa-panel-icon { height: 2rem; width: 2rem; background: #34303a !important; color: #9ee0d4 !important; }

.riraa-dashboard [class~='text-[#252930]'],
.riraa-dashboard [class~='text-[#303741]'],
.riraa-dashboard [class~='text-[#30363e]'],
.riraa-dashboard [class~='text-[#3f4650]'],
.riraa-dashboard [class~='text-[#4e5661]'],
.riraa-dashboard [class~='text-[#5c6470]'] { color: #d8d2dc !important; }

.riraa-dashboard [class~='text-[#777f89]'],
.riraa-dashboard [class~='text-[#7d848e]'],
.riraa-dashboard [class~='text-[#808792]'],
.riraa-dashboard [class~='text-[#858c96]'],
.riraa-dashboard [class~='text-[#8b919a]'],
.riraa-dashboard [class~='text-[#9299a2]'] { color: #8e8795 !important; }

.riraa-dashboard [class~='bg-white'] { background: #2a2830 !important; }
.riraa-dashboard [class~='bg-[#eef0f3]'],
.riraa-dashboard [class~='bg-[#f5f6f8]'] { background: #36313b !important; }

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard {
    background: #f7f8fa;
    color: #20242b;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard h1 {
    color: #20242b !important;
    -webkit-text-fill-color: #20242b;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard h2 {
    color: #252930;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-eyebrow {
    color: #8b919a;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-share-button {
    border-color: #d9d3dd !important;
    background: #fff !important;
    color: #5f5866 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-share-button:hover {
    border-color: #b8afbe !important;
    background: #faf9fb !important;
    color: #28232d !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-metric-card,
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard-card {
    border-color: #e3e6ea;
    background: #fff;
    box-shadow: 0 1px 2px rgba(24, 35, 52, 0.025);
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-metric-card p {
    color: #7d858f;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-metric-card strong {
    color: #252a31;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-progress-track {
    background: #eef0f3;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard-card > div.border-b,
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard-card > div.border-t,
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard-card .border-t {
    border-color: #eceef1 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard-card .divide-y > * {
    border-color: #f0f1f3 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard-card [class~='bg-[#fafbfc]'] {
    background: #fafbfc !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-panel-action {
    border-color: #d9d3dd;
    background: #fff;
    color: #5c5562;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-panel-action:hover {
    border-color: #b8afbe;
    background: #faf9fb;
    color: #28232d;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-tab {
    color: #9299a2;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-tab span {
    color: #b0b5bd;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-tab--active {
    color: #303741;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-tab--active::after {
    background: #f04b67;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-work-row:hover,
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-project-row:hover {
    background: #fafbfc;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-work-row strong,
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-project-row strong {
    color: #444b55;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-work-row small,
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-project-row small {
    color: #969da6;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-project-arrow {
    color: #b2b7bf;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-panel-icon {
    background: #edf2ff !important;
    color: #3b72d9 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='text-[#252930]'],
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='text-[#303741]'],
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='text-[#30363e]'],
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='text-[#3f4650]'],
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='text-[#4e5661]'],
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='text-[#5c6470]'] {
    color: #3f4650 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='text-[#777f89]'],
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='text-[#7d848e]'],
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='text-[#808792]'],
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='text-[#858c96]'],
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='text-[#8b919a]'],
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='text-[#9299a2]'] {
    color: #7d858f !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='bg-white'] {
    background: #fff !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='bg-[#eef0f3]'],
:global(html:not(.dark) body:has(.riraa-dashboard)) .riraa-dashboard [class~='bg-[#f5f6f8]'] {
    background: #eef0f3 !important;
}

:global(html:not(.dark)) .riraa-dashboard {
    background: #f7f8fa !important;
    color: #20242b !important;
}

:global(html:not(.dark)) .riraa-dashboard h1 {
    color: #20242b !important;
    -webkit-text-fill-color: #20242b !important;
}

:global(html:not(.dark)) .riraa-dashboard h2 {
    color: #252930 !important;
}

:global(html:not(.dark)) .riraa-dashboard .riraa-metric-card,
:global(html:not(.dark)) .riraa-dashboard .riraa-dashboard-card {
    border-color: #e3e6ea !important;
    background: #fff !important;
}

:global(html:not(.dark)) .riraa-dashboard .riraa-metric-card p,
:global(html:not(.dark)) .riraa-dashboard .riraa-work-row small,
:global(html:not(.dark)) .riraa-dashboard .riraa-project-row small {
    color: #7d858f !important;
}

:global(html:not(.dark)) .riraa-dashboard .riraa-metric-card strong,
:global(html:not(.dark)) .riraa-dashboard .riraa-work-row strong,
:global(html:not(.dark)) .riraa-dashboard .riraa-project-row strong {
    color: #252a31 !important;
}

:global(html:not(.dark)) .riraa-dashboard .riraa-progress-track {
    background: #eef0f3 !important;
}

:global(html:not(.dark)) .riraa-dashboard .riraa-tab,
:global(html:not(.dark)) .riraa-dashboard .riraa-panel-action,
:global(html:not(.dark)) .riraa-dashboard .riraa-panel-action:hover {
    color: #5c5562 !important;
}

:global(html:not(.dark)) .riraa-dashboard .riraa-tab--active {
    color: #303741 !important;
}

:global(html:not(.dark)) .riraa-dashboard .riraa-work-row:hover,
:global(html:not(.dark)) .riraa-dashboard .riraa-project-row:hover {
    background: #fafbfc !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard) {
    background: #f7f8fa !important;
    color: #20242b !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard h1) {
    color: #20242b !important;
    -webkit-text-fill-color: #20242b !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard h2) {
    color: #252930 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-metric-card),
:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-dashboard-card) {
    border-color: #e3e6ea !important;
    background: #fff !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-metric-card p),
:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-work-row small),
:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-project-row small) {
    color: #7d858f !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-metric-card strong),
:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-work-row strong),
:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-project-row strong) {
    color: #252a31 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-progress-track) {
    background: #eef0f3 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-tab),
:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-panel-action) {
    color: #5c5562 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-tab--active) {
    color: #303741 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-work-row:hover),
:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-project-row:hover) {
    background: #fafbfc !important;
}

/* These surfaces also carry dark-mode utility overrides, so keep their light
   treatment explicit and scoped to the dashboard page. */
:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-dashboard-footer) {
    background: #fafbfc !important;
    color: #7c838d !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-empty-state) {
    background: #fafbfc !important;
    color: #808792 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-panel-icon) {
    background: #edf2ff !important;
    color: #3b72d9 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-dashboard-prompt) {
    border-color: #e5e7eb !important;
    background: #fff !important;
    color: #3f4650 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-dashboard-prompt-title) {
    color: #3f4650 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-dashboard-prompt-description) {
    color: #858c96 !important;
}

:global(html:not(.dark) body:has(.riraa-dashboard) .riraa-dashboard .riraa-dashboard-prompt .riraa-landing-pill) {
    border: 1px solid #d9d3dd;
    border-radius: 999px;
    background: #fff;
    color: #5c5562 !important;
}

@media (max-width: 640px) {
    .riraa-work-row { padding-left: 1.25rem; padding-right: 1.25rem; }
    .riraa-work-row small { white-space: normal; }
    .riraa-metric-card { min-height: 8.4rem; }
}

@media (prefers-reduced-motion: reduce) {
    .riraa-panel-action,
    .riraa-work-row,
    .riraa-project-row { transition: none; }
}
</style>
