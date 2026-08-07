<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    CheckCircle2,
    ChevronDown,
    CircleDot,
    FolderKanban,
    Layers3,
    MessageSquareText,
    Play,
    ShieldCheck,
    Sparkles,
    TicketCheck,
    Zap,
} from '@lucide/vue';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import RiraaMark from '@/Components/RiraaMark.vue';
import { Button } from '@/Components/UI/button';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const primaryUrl = computed(() =>
    user.value ? route('dashboard') : route('register'),
);
const primaryLabel = computed(() =>
    user.value ? 'Open your workspace' : 'Get started free',
);

const navItems = [
    { label: 'Product', href: '#product', dropdown: true },
    { label: 'Solutions', href: '#solutions', dropdown: true },
    { label: 'Resources', href: '#resources', dropdown: true },
];

const workflowSteps = [
    { label: 'Set up a workspace', color: 'violet', icon: FolderKanban },
    { label: 'Plan on your board', color: 'rose', icon: Layers3 },
    { label: 'Move work to done', color: 'teal', icon: CheckCircle2 },
];

const capabilities = [
    {
        eyebrow: 'WORKSPACES',
        title: 'Keep the big picture close.',
        body: 'Give every team a shared home for projects, people, and the context behind the work.',
        icon: FolderKanban,
        color: 'violet',
    },
    {
        eyebrow: 'BOARDS',
        title: 'Make progress visible.',
        body: 'Shape a workflow that fits the way your team works, then see what needs attention at a glance.',
        icon: TicketCheck,
        color: 'orange',
    },
    {
        eyebrow: 'COLLABORATION',
        title: 'Decisions stay with the work.',
        body: 'Comments, files, owners, and due dates travel with every ticket from idea to delivery.',
        icon: MessageSquareText,
        color: 'teal',
    },
];

const footerGroups = [
    { label: 'Product', links: ['Workspaces', 'Boards', 'Tickets', 'Permissions'] },
    { label: 'Company', links: ['About Riraa', 'Careers', 'Contact us', 'Security'] },
    { label: 'Resources', links: ['Help center', 'Getting started', 'Templates', 'Release notes'] },
];
</script>

<template>
    <Head title="Project management for focused teams">
        <meta
            name="description"
            content="Riraa brings company workspaces, boards, tickets, and team collaboration into one focused project management experience."
        />
    </Head>

    <div class="riraa-home min-h-screen overflow-hidden bg-white text-[#17151c]">
        <header class="riraa-nav sticky top-0 z-40 border-b border-[#ebe7ee] bg-white/90 backdrop-blur-xl">
            <div class="mx-auto flex h-[68px] max-w-[1240px] items-center gap-8 px-5 sm:px-8">
                <Link :href="route('welcome')" class="flex items-center gap-2.5" aria-label="Riraa home">
                    <RiraaMark />
                    <span class="text-[18px] font-semibold tracking-[-0.04em]">riraa</span>
                </Link>

                <nav class="hidden items-center gap-7 text-[13px] font-medium text-[#5f5966] md:flex" aria-label="Primary navigation">
                    <a v-for="item in navItems" :key="item.label" :href="item.href" class="inline-flex items-center gap-1 transition hover:text-[#17151c]">
                        {{ item.label }}
                        <ChevronDown v-if="item.dropdown" class="size-3.5" />
                    </a>
                    <a href="#pricing" class="transition hover:text-[#17151c]">Pricing</a>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <Link v-if="!user" :href="route('login')" class="hidden rounded-full px-3.5 py-2 text-[13px] font-medium text-[#5f5966] transition hover:bg-[#f6f3f7] hover:text-[#17151c] sm:inline-flex">
                        Log in
                    </Link>
                    <Button as-child size="sm" class="h-9 rounded-full bg-[#17151c] px-4 text-[13px] shadow-none hover:bg-[#302b35]">
                        <Link :href="primaryUrl">{{ user ? 'Dashboard' : 'Get started' }}</Link>
                    </Button>
                </div>
            </div>
        </header>

        <main>
            <section id="product" class="riraa-hero relative isolate border-b border-[#ebe7ee] px-5 pb-0 pt-16 sm:px-8 sm:pt-24">
                <div class="riraa-dot-grid pointer-events-none absolute inset-0 -z-10 opacity-70" aria-hidden="true" />
                <div class="riraa-hero-orb riraa-hero-orb--left pointer-events-none absolute -left-40 top-36 -z-10 size-[28rem] rounded-full bg-[#f8c8d4]/40 blur-3xl" aria-hidden="true" />
                <div class="riraa-hero-orb riraa-hero-orb--right pointer-events-none absolute -right-40 top-16 -z-10 size-[30rem] rounded-full bg-[#c9edeb]/45 blur-3xl" aria-hidden="true" />

                <div class="mx-auto max-w-[1160px]">
                    <div class="relative mx-auto max-w-[800px] text-center">
                        <div class="riraa-float-chip riraa-float-chip--launch hidden rounded-full border border-[#d8d1dc] bg-white px-3 py-2 text-left shadow-[0_10px_30px_rgba(54,38,65,0.08)] sm:flex">
                            <span class="mr-2 flex size-6 items-center justify-center rounded-full bg-[#d8f3d7] text-[#278c49]"><Check class="size-3.5" /></span>
                            <span><strong>Launch Planner</strong><small>Planning in motion</small></span>
                        </div>
                        <div class="riraa-float-chip riraa-float-chip--review hidden rounded-full border border-[#d8d1dc] bg-white px-3 py-2 text-left shadow-[0_10px_30px_rgba(54,38,65,0.08)] sm:flex">
                            <span class="mr-2 flex size-6 items-center justify-center rounded-full bg-[#ffe6c8] text-[#c76b24]"><CircleDot class="size-3.5" /></span>
                            <span><strong>Review this week</strong><small>4 tickets need you</small></span>
                        </div>
                        <div class="riraa-float-chip riraa-float-chip--shipped hidden rounded-full border border-[#d8d1dc] bg-white px-3 py-2 text-left shadow-[0_10px_30px_rgba(54,38,65,0.08)] sm:flex">
                            <span class="mr-2 flex size-6 items-center justify-center rounded-full bg-[#d7f2ef] text-[#11877f]"><CheckCircle2 class="size-3.5" /></span>
                            <span><strong>Product shipped!</strong><small>Nice work, team</small></span>
                        </div>

                        <p class="riraa-kicker">PROJECT MANAGEMENT, WITH A LITTLE MORE HUMANITY</p>
                        <h1 class="mt-5 text-[clamp(3.4rem,8vw,6.9rem)] font-semibold leading-[0.93] tracking-[-0.075em]">
                            The calm place<br />
                            <span class="text-[#f04b67]">to move work</span>
                            <span class="text-[#6954d8]"> forward.</span>
                        </h1>
                        <p class="mx-auto mt-7 max-w-[560px] text-base leading-7 text-[#68616e] sm:text-lg">
                            Riraa gives your team one clear place to plan projects, share context, and turn good ideas into finished work.
                        </p>
                        <div class="mt-8 flex flex-wrap justify-center gap-3">
                            <Button as-child size="lg" class="h-12 rounded-full bg-[#f04b67] px-7 text-sm shadow-[0_12px_24px_rgba(240,75,103,0.2)] hover:bg-[#db3856]">
                                <Link :href="primaryUrl">{{ primaryLabel }} <ArrowRight class="size-4" /></Link>
                            </Button>
                            <a href="#workflow" class="inline-flex h-12 items-center gap-2 rounded-full border border-[#d8d1dc] px-6 text-sm font-medium text-[#35303a] transition hover:border-[#aaa0b0] hover:bg-white">
                                <Play class="size-3.5 fill-current" /> See how it works
                            </a>
                        </div>
                    </div>

                    <div class="riraa-hero-product relative mx-auto mt-16 max-w-[1000px] sm:mt-20">
                        <div class="riraa-flow-line riraa-flow-line--one hidden sm:block" aria-hidden="true" />
                        <div class="riraa-flow-line riraa-flow-line--two hidden sm:block" aria-hidden="true" />
                        <div class="riraa-avatar riraa-avatar--one hidden sm:flex">A</div>
                        <div class="riraa-avatar riraa-avatar--two hidden sm:flex">M</div>
                        <div class="riraa-avatar riraa-avatar--three hidden sm:flex">S</div>
                        <div class="riraa-board-window">
                            <div class="riraa-window-topbar">
                                <div class="flex items-center gap-2"><span class="size-2 rounded-full bg-[#f04b67]" /><span class="size-2 rounded-full bg-[#f1b84b]" /><span class="size-2 rounded-full bg-[#57b89b]" /></div>
                                <span class="text-[10px] font-medium text-[#8c8590]">Riraa / Launch campaign</span>
                                <span class="size-5 rounded-full bg-[#ebe5ef]" />
                            </div>
                            <div class="grid min-h-[300px] grid-cols-[150px_1fr] sm:grid-cols-[190px_1fr]">
                                <aside class="hidden border-r border-[#ece8ed] bg-[#fbfafb] p-4 sm:block">
                                    <div class="mb-7 flex items-center gap-2"><RiraaMark class="size-6 rounded-md" icon-class="size-4 fill-current" /><span class="text-xs font-semibold">riraa</span></div>
                                    <div class="space-y-2 text-[10px] text-[#8c8590]"><div class="rounded-md bg-[#f3e9ef] px-2 py-1.5 font-semibold text-[#f04b67]">Home</div><div class="px-2 py-1.5">My tasks</div><div class="px-2 py-1.5">Inbox <span class="float-right rounded bg-[#f04b67] px-1 text-white">3</span></div><div class="mt-5 px-2 py-1.5 font-semibold text-[#5b5560]">Your workspaces</div><div class="px-2 py-1.5">Launch campaign</div><div class="px-2 py-1.5">Website refresh</div></div>
                                </aside>
                                <div class="overflow-hidden bg-white p-4 sm:p-6">
                                    <div class="flex items-start justify-between gap-3"><div><div class="mb-2 h-2 w-20 rounded-full bg-[#f1edf2]" /><h2 class="text-base font-semibold tracking-[-0.03em] text-[#29252d] sm:text-xl">Launch campaign</h2><p class="mt-1 text-[10px] text-[#99929c]">Make the next big thing happen.</p></div><div class="rounded-md bg-[#f04b67] px-2.5 py-1.5 text-[9px] font-semibold text-white">+ Add task</div></div>
                                    <div class="mt-6 grid grid-cols-3 gap-2 border-b border-[#eeeaf0] pb-2 text-[9px] font-medium text-[#817984]"><span class="border-b-2 border-[#f04b67] pb-2 text-[#f04b67]">Board</span><span>List</span><span>Timeline</span></div>
                                    <div class="mt-5 grid grid-cols-3 gap-3">
                                        <div v-for="(column, columnIndex) in ['To do', 'In progress', 'Done']" :key="column" class="min-w-0"><div class="mb-3 flex items-center justify-between text-[9px] font-semibold text-[#635c66]"><span><i class="mr-1 inline-block size-1.5 rounded-full" :class="columnIndex === 0 ? 'bg-[#c6b2f5]' : columnIndex === 1 ? 'bg-[#f6b35e]' : 'bg-[#6ed0bc]'" />{{ column }}</span><span class="text-[#aaa3ad]">{{ columnIndex + 2 }}</span></div><div v-for="task in (columnIndex === 0 ? ['Brief the team', 'Map the launch'] : columnIndex === 1 ? ['Design the page', 'Review copy'] : ['Set up analytics', 'Share the update'])" :key="task" class="mb-2 rounded-md border border-[#eee9ef] bg-white p-2 shadow-[0_2px_7px_rgba(60,40,70,0.04)]"><div class="h-1.5 w-4/5 rounded-full bg-[#e9e4eb]" /><div class="mt-2 text-[8px] leading-3 text-[#706976]">{{ task }}</div><div class="mt-2 flex items-center justify-between"><span class="size-3 rounded-full" :class="columnIndex === 0 ? 'bg-[#f3c2d2]' : columnIndex === 1 ? 'bg-[#c9c1f0]' : 'bg-[#bfe9df]'" /><span class="text-[8px] text-[#aaa3ad]">{{ columnIndex === 2 ? 'Done' : 'Fri' }}</span></div></div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-b border-[#ebe7ee] bg-white px-5 py-12 sm:px-8">
                <div class="mx-auto flex max-w-[1050px] flex-col items-center gap-7 sm:flex-row sm:justify-between">
                    <p class="max-w-[220px] text-center text-xs font-semibold leading-5 text-[#69616d] sm:text-left">Trusted by teams who want more done, with less noise.</p>
                    <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-5 text-sm font-semibold tracking-[-0.04em] text-[#8c8590] sm:gap-x-12"><span>northstar</span><span>arc + co.</span><span>MERIDIAN</span><span class="flex items-center gap-1"><span class="size-4 rounded-full border-2 border-[#8c8590]" />orbit</span><span>HUMANELY</span></div>
                </div>
            </section>

            <section id="solutions" class="riraa-section px-5 py-24 sm:px-8 sm:py-32">
                <div class="mx-auto grid max-w-[1160px] gap-14 lg:grid-cols-[0.8fr_1.2fr] lg:items-center lg:gap-24">
                    <div>
                        <p class="riraa-kicker text-[#f04b67]">A BETTER WAY TO WORK</p>
                        <h2 class="mt-5 max-w-[490px] text-4xl font-semibold leading-[1.02] tracking-[-0.065em] sm:text-6xl">AI that keeps your team moving together.</h2>
                        <p class="mt-6 max-w-[440px] text-base leading-7 text-[#68616e]">A clear place for the plan. A quick place for the update. A thoughtful place for the decisions that keep a project moving.</p>
                        <div class="mt-8 space-y-4"><div class="flex items-start gap-3"><span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-[#f9e3e8] text-[#f04b67]"><Check class="size-3.5" /></span><span class="text-sm leading-6 text-[#4e4852]">Bring company context and daily work into one view.</span></div><div class="flex items-start gap-3"><span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-[#e1f4f1] text-[#168e82]"><Check class="size-3.5" /></span><span class="text-sm leading-6 text-[#4e4852]">Make ownership, status, and next steps obvious.</span></div></div>
                        <Button as-child variant="outline" class="mt-9 h-10 rounded-full border-[#cfc6d2] bg-white px-5 text-sm shadow-none"><Link :href="primaryUrl">Explore Riraa <ArrowRight class="size-4" /></Link></Button>
                    </div>
                    <div class="riraa-ai-card">
                        <div class="riraa-ai-toolbar"><div class="flex items-center gap-2"><span class="flex size-7 items-center justify-center rounded-lg bg-[#f04b67] text-white"><Sparkles class="size-3.5" /></span><span class="text-xs font-semibold text-[#49414c]">Riraa assistant</span></div><span class="rounded-full bg-[#e8f5f2] px-2.5 py-1 text-[9px] font-semibold text-[#168e82]">Ready to help</span></div>
                        <div class="grid gap-4 p-5 sm:grid-cols-[1fr_0.8fr] sm:p-7"><div><p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-[#a19aa5]">Project pulse</p><h3 class="mt-3 text-xl font-semibold tracking-[-0.05em] text-[#29252d]">Launch campaign</h3><div class="mt-6 space-y-3"><div class="flex items-center gap-3 rounded-lg bg-[#fbfafb] p-3"><span class="flex size-7 items-center justify-center rounded-full bg-[#f8d6e0] text-[10px] font-semibold text-[#d54b69]">AM</span><div class="min-w-0 flex-1"><div class="h-1.5 w-3/4 rounded-full bg-[#e8e3e9]" /><div class="mt-2 text-[9px] text-[#8e8792]">Draft launch brief</div></div><span class="text-[9px] font-semibold text-[#f04b67]">Due today</span></div><div class="flex items-center gap-3 rounded-lg bg-[#fbfafb] p-3"><span class="flex size-7 items-center justify-center rounded-full bg-[#d6edf0] text-[10px] font-semibold text-[#168e82]">SK</span><div class="min-w-0 flex-1"><div class="h-1.5 w-1/2 rounded-full bg-[#e8e3e9]" /><div class="mt-2 text-[9px] text-[#8e8792]">Approve homepage copy</div></div><span class="text-[9px] text-[#8e8792]">Tomorrow</span></div><div class="flex items-center gap-3 rounded-lg bg-[#fbfafb] p-3"><span class="flex size-7 items-center justify-center rounded-full bg-[#e6ddf7] text-[10px] font-semibold text-[#6954d8]">JR</span><div class="min-w-0 flex-1"><div class="h-1.5 w-2/3 rounded-full bg-[#e8e3e9]" /><div class="mt-2 text-[9px] text-[#8e8792]">Share launch update</div></div><span class="text-[9px] text-[#168e82]">On track</span></div></div></div><div class="rounded-xl bg-[#faf4f7] p-4 sm:p-5"><div class="flex items-center justify-between"><span class="text-[10px] font-semibold text-[#605864]">Project health</span><span class="text-[10px] font-semibold text-[#168e82]">Good</span></div><div class="riraa-health-ring mt-5"><div><strong>76%</strong><small>on track</small></div></div><div class="mt-5 space-y-2.5 text-[9px] text-[#817984]"><div class="flex justify-between"><span>Open tasks</span><strong class="text-[#514955]">24</strong></div><div class="flex justify-between"><span>Completed</span><strong class="text-[#514955]">18</strong></div><div class="flex justify-between"><span>Contributors</span><strong class="text-[#514955]">8</strong></div></div></div></div>
                    </div>
                </div>
            </section>

            <section id="workflow" class="riraa-ink-section relative overflow-hidden bg-[#17151c] px-5 py-24 text-white sm:px-8 sm:py-32">
                <div class="pointer-events-none absolute -right-32 top-0 size-[34rem] rounded-full bg-[#6954d8]/15 blur-3xl" aria-hidden="true" />
                <div class="mx-auto max-w-[1160px]">
                    <div class="flex flex-col justify-between gap-8 lg:flex-row lg:items-end"><div><p class="riraa-kicker text-[#e9a2b4]">ONE PLACE FOR THE WHOLE STORY</p><h2 class="mt-5 max-w-[590px] text-4xl font-semibold leading-[1.02] tracking-[-0.06em] sm:text-6xl">Deliver real productivity for every team.</h2></div><p class="max-w-[330px] text-sm leading-6 text-white/60">From first thought to final checkmark, Riraa helps work feel lighter and more connected.</p></div>
                    <div class="mt-14 flex gap-2 overflow-x-auto pb-2"><button v-for="(step, index) in workflowSteps" :key="step.label" class="flex shrink-0 items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-medium text-white/80 transition hover:bg-white/15"><span class="flex size-5 items-center justify-center rounded-full" :class="index === 0 ? 'bg-[#c5b4f3] text-[#4d3d9f]' : index === 1 ? 'bg-[#f6b5c5] text-[#9c3c54]' : 'bg-[#9ee0d4] text-[#13776f]'"><component :is="step.icon" class="size-3" /></span>{{ step.label }}</button></div>
                    <div class="riraa-dark-product mt-8 grid gap-0 overflow-hidden rounded-2xl border border-white/10 bg-[#25232b] lg:grid-cols-[190px_1fr]">
                        <aside class="hidden border-r border-white/10 bg-[#201e25] p-5 lg:block"><div class="mb-8 flex items-center gap-2"><RiraaMark class="size-6 rounded-md" icon-class="size-4 fill-current" /><span class="text-xs font-semibold">riraa</span></div><div class="space-y-3 text-[10px] text-white/45"><div class="rounded-md bg-white/10 px-2 py-2 text-white/80">Overview</div><div class="px-2">My tasks</div><div class="px-2">Inbox <span class="float-right rounded bg-[#f04b67] px-1 text-white">3</span></div><div class="pt-5 px-2 font-semibold text-white/65">Workspaces</div><div class="px-2">Launch campaign</div><div class="px-2">Website refresh</div></div></aside>
                        <div class="p-5 sm:p-7"><div class="flex items-start justify-between"><div><p class="text-[10px] uppercase tracking-[0.18em] text-white/35">Launch campaign</p><h3 class="mt-2 text-lg font-semibold tracking-[-0.03em]">Project overview</h3></div><div class="flex items-center gap-2"><span class="hidden rounded-md border border-white/10 px-3 py-1.5 text-[9px] text-white/60 sm:block">Share</span><span class="rounded-md bg-[#f04b67] px-3 py-1.5 text-[9px] font-semibold">Add task</span></div></div><div class="mt-6 grid gap-3 sm:grid-cols-3"><div v-for="(value, label) in { 'On track': '76%', 'Open tasks': '24', 'Due this week': '8' }" :key="label" class="rounded-xl border border-white/10 bg-white/[0.04] p-4"><div class="text-[10px] text-white/45">{{ label }}</div><div class="mt-2 text-2xl font-semibold tracking-[-0.05em]">{{ value }}</div><div class="mt-2 h-1 rounded-full bg-white/10"><div class="h-1 rounded-full" :class="label === 'On track' ? 'w-3/4 bg-[#9ee0d4]' : label === 'Open tasks' ? 'w-1/2 bg-[#f6b5c5]' : 'w-1/3 bg-[#c5b4f3]'" /></div></div></div><div class="mt-4 overflow-hidden rounded-xl border border-white/10 bg-[#1d1b21]"><div class="grid grid-cols-[1.3fr_0.8fr_0.8fr_0.5fr] border-b border-white/10 px-4 py-3 text-[9px] uppercase tracking-[0.14em] text-white/35"><span>Task</span><span>Owner</span><span>Status</span><span>Due</span></div><div v-for="task in [{name:'Finalize campaign brief',owner:'AM',status:'In progress',tone:'yellow',due:'Today'},{name:'Approve visual direction',owner:'SK',status:'Review',tone:'pink',due:'Fri'},{name:'Schedule social launch',owner:'JR',status:'Done',tone:'green',due:'Done'},{name:'Share launch update',owner:'ML',status:'To do',tone:'purple',due:'Mon'}]" :key="task.name" class="grid grid-cols-[1.3fr_0.8fr_0.8fr_0.5fr] items-center border-b border-white/5 px-4 py-3 text-[10px] text-white/65 last:border-0"><span class="flex min-w-0 items-center gap-2"><span class="size-1.5 shrink-0 rounded-full" :class="task.tone === 'yellow' ? 'bg-[#f6b35e]' : task.tone === 'pink' ? 'bg-[#f6b5c5]' : task.tone === 'green' ? 'bg-[#9ee0d4]' : 'bg-[#c5b4f3]'" /><span class="truncate">{{ task.name }}</span></span><span class="flex size-5 items-center justify-center rounded-full bg-white/10 text-[8px]">{{ task.owner }}</span><span>{{ task.status }}</span><span class="text-white/40">{{ task.due }}</span></div></div></div>
                    </div>
                </div>
            </section>

            <section id="resources" class="px-5 py-24 sm:px-8 sm:py-32">
                <div class="mx-auto max-w-[1160px]"><div class="max-w-[570px]"><p class="riraa-kicker text-[#6954d8]">ALL THE IMPORTANT PIECES</p><h2 class="mt-5 text-4xl font-semibold leading-[1.02] tracking-[-0.06em] sm:text-6xl">Your team just got bigger.</h2><p class="mt-6 text-base leading-7 text-[#68616e]">Invite the right people, give them the right context, and let everyone do their best work.</p></div><div class="mt-14 grid gap-4 md:grid-cols-3"> <article v-for="capability in capabilities" :key="capability.title" class="riraa-capability-card group"><span class="flex size-11 items-center justify-center rounded-2xl" :class="capability.color === 'violet' ? 'bg-[#eee9fb] text-[#6954d8]' : capability.color === 'orange' ? 'bg-[#fff0db] text-[#cb722a]' : 'bg-[#e1f4f1] text-[#168e82]'"><component :is="capability.icon" class="size-5" /></span><p class="mt-9 text-[10px] font-semibold tracking-[0.16em] text-[#938b96]">{{ capability.eyebrow }}</p><h3 class="mt-3 text-xl font-semibold leading-tight tracking-[-0.04em]">{{ capability.title }}</h3><p class="mt-3 text-sm leading-6 text-[#756e79]">{{ capability.body }}</p><a :href="capability.color === 'violet' ? '#product' : '#workflow'" class="mt-8 inline-flex items-center gap-2 text-xs font-semibold text-[#4c4551]">Learn more <ArrowRight class="size-3.5 transition group-hover:translate-x-1" /></a></article></div></div>
            </section>

            <section id="pricing" class="border-y border-[#ebe7ee] bg-[#faf8fa] px-5 py-20 sm:px-8 sm:py-24"><div class="mx-auto flex max-w-[1160px] flex-col justify-between gap-8 md:flex-row md:items-center"><div><p class="riraa-kicker text-[#168e82]">GET STARTED EASILY</p><h2 class="mt-4 text-3xl font-semibold tracking-[-0.05em] sm:text-4xl">A clearer day is a few clicks away.</h2><p class="mt-3 max-w-[500px] text-sm leading-6 text-[#756e79]">Start with one workspace. Add your team when you’re ready. Riraa grows with the work.</p></div><div class="flex flex-wrap gap-3"><Button as-child size="lg" class="h-11 rounded-full bg-[#17151c] px-6 shadow-none hover:bg-[#302b35]"><Link :href="primaryUrl">{{ primaryLabel }} <ArrowRight class="size-4" /></Link></Button><Button v-if="!user" as-child variant="outline" size="lg" class="h-11 rounded-full border-[#cfc6d2] bg-white px-6 shadow-none"><Link :href="route('login')">Sign in</Link></Button></div></div></section>

            <section class="bg-white px-5 py-24 sm:px-8 sm:py-32"><div class="mx-auto max-w-[1160px]"><div class="flex flex-col justify-between gap-7 md:flex-row md:items-end"><div><p class="riraa-kicker text-[#f04b67]">BUILT FOR THE WAY YOU WORK</p><h2 class="mt-4 max-w-[610px] text-4xl font-semibold leading-[1.02] tracking-[-0.06em] sm:text-6xl">Simple enough to start. Strong enough to stay.</h2></div><span class="flex size-12 items-center justify-center rounded-full bg-[#17151c] text-white"><ArrowRight class="size-5 -rotate-45" /></span></div><div class="mt-14 grid gap-4 md:grid-cols-2"><div class="rounded-2xl border border-[#ebe7ee] bg-[#fbfafb] p-6 sm:p-8"><div class="flex items-center gap-3"><span class="flex size-10 items-center justify-center rounded-xl bg-[#f9e3e8] text-[#f04b67]"><ShieldCheck class="size-5" /></span><h3 class="text-lg font-semibold tracking-[-0.03em]">Clear boundaries, shared trust.</h3></div><p class="mt-6 max-w-[400px] text-sm leading-6 text-[#756e79]">Role-aware access and company structure give admins confidence without adding friction for the people doing the work.</p></div><div class="rounded-2xl bg-[#eee9fb] p-6 sm:p-8"><div class="flex items-center gap-3"><span class="flex size-10 items-center justify-center rounded-xl bg-white text-[#6954d8]"><Zap class="size-5" /></span><h3 class="text-lg font-semibold tracking-[-0.03em]">Less setup. More momentum.</h3></div><p class="mt-6 max-w-[400px] text-sm leading-6 text-[#655b76]">Flexible boards, useful defaults, and lightweight collaboration help a project get moving before the meeting starts.</p></div></div></div></section>
        </main>

        <footer class="bg-[#17151c] px-5 py-16 text-white sm:px-8 sm:py-20"><div class="mx-auto max-w-[1160px]"><div class="grid gap-12 border-b border-white/10 pb-14 lg:grid-cols-[1.2fr_2fr]"><div><Link :href="route('welcome')" class="flex items-center gap-2.5"><RiraaMark /><span class="text-[18px] font-semibold tracking-[-0.04em]">riraa</span></Link><h2 class="mt-8 max-w-[340px] text-3xl font-semibold leading-tight tracking-[-0.05em]">Make room for better work.</h2><Button as-child size="lg" class="mt-7 h-11 rounded-full bg-white px-6 text-[#17151c] shadow-none hover:bg-[#f5eef3]"><Link :href="primaryUrl">Get started <ArrowRight class="size-4" /></Link></Button></div><div class="grid grid-cols-2 gap-9 sm:grid-cols-3"> <div v-for="group in footerGroups" :key="group.label"><p class="text-xs font-semibold text-white/40">{{ group.label }}</p><ul class="mt-5 space-y-3 text-sm text-white/65"><li v-for="link in group.links" :key="link"><a href="#" class="transition hover:text-white">{{ link }}</a></li></ul></div></div></div><div class="flex flex-col justify-between gap-4 pt-6 text-xs text-white/40 sm:flex-row"><span>© {{ new Date().getFullYear() }} Riraa. All rights reserved.</span><span>Focused project management for modern teams.</span></div></div></footer>
    </div>
</template>

<style scoped>
.riraa-home {
    font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}

.riraa-nav :deep(a),
.riraa-home a {
    text-decoration: none;
}

.riraa-kicker {
    font-size: 0.67rem;
    font-weight: 700;
    letter-spacing: 0.16em;
    line-height: 1.4;
}

.riraa-dot-grid {
    background-image: radial-gradient(#d8d0da 0.75px, transparent 0.75px);
    background-position: center top;
    background-size: 17px 17px;
    mask-image: linear-gradient(to bottom, black 0%, black 68%, transparent 100%);
}

.riraa-hero {
    background: linear-gradient(180deg, #fff 0%, #fff 74%, #fff9fb 100%);
}

.riraa-float-chip {
    position: absolute;
    z-index: 2;
    align-items: center;
    font-size: 0.68rem;
}

.riraa-float-chip span:last-child {
    display: flex;
    flex-direction: column;
    gap: 1px;
    white-space: nowrap;
}

.riraa-float-chip strong {
    color: #48414c;
    font-size: 0.68rem;
    font-weight: 600;
}

.riraa-float-chip small {
    color: #a19aa5;
    font-size: 0.58rem;
}

.riraa-float-chip--launch {
    left: -3.8rem;
    top: 8.4rem;
}

.riraa-float-chip--review {
    right: -4.2rem;
    top: 4.5rem;
}

.riraa-float-chip--shipped {
    right: -5.5rem;
    top: 16rem;
}

.riraa-hero-product {
    padding: 0 2.5%;
}

.riraa-board-window,
.riraa-ai-card,
.riraa-dark-product {
    box-shadow: 0 24px 60px rgba(68, 39, 75, 0.12), 0 2px 6px rgba(68, 39, 75, 0.06);
}

.riraa-board-window {
    position: relative;
    overflow: hidden;
    border: 1px solid #e1dbe4;
    border-radius: 1.15rem 1.15rem 0 0;
    background: #fff;
}

.riraa-window-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #ece8ed;
    padding: 0.7rem 1rem;
}

.riraa-flow-line {
    position: absolute;
    z-index: 1;
    height: 1px;
    border-top: 1px dashed #ef94a9;
    opacity: 0.8;
}

.riraa-flow-line::after {
    position: absolute;
    top: -4px;
    right: -2px;
    height: 7px;
    width: 7px;
    border-radius: 999px;
    background: #f04b67;
    content: '';
}

.riraa-flow-line--one {
    left: -2%;
    top: 31%;
    width: 12%;
    transform: rotate(-12deg);
}

.riraa-flow-line--two {
    right: -2%;
    top: 22%;
    width: 15%;
    transform: rotate(12deg);
}

.riraa-avatar {
    position: absolute;
    z-index: 2;
    align-items: center;
    justify-content: center;
    height: 1.9rem;
    width: 1.9rem;
    border: 3px solid #fff;
    border-radius: 999px;
    font-size: 0.6rem;
    font-weight: 700;
    color: #fff;
    box-shadow: 0 5px 14px rgba(38, 27, 43, 0.13);
}

.riraa-avatar--one { left: 7%; top: 25%; background: #e28b6d; }
.riraa-avatar--two { right: 7%; top: 17%; background: #6f62c6; }
.riraa-avatar--three { right: 17%; top: 55%; background: #279f96; }

.riraa-section {
    background: linear-gradient(180deg, #fff 0%, #fff 82%, #fdf7fa 100%);
}

.riraa-ai-card {
    overflow: hidden;
    border: 1px solid #e4dce5;
    border-radius: 1.25rem;
    background: #fff;
}

.riraa-ai-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #eee9ef;
    padding: 0.8rem 1.25rem;
}

.riraa-health-ring {
    display: flex;
    height: 7rem;
    width: 7rem;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: conic-gradient(#168e82 0 76%, #d9eeeb 76% 100%);
}

.riraa-health-ring > div {
    display: flex;
    height: 5.7rem;
    width: 5.7rem;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: #faf4f7;
}

.riraa-health-ring strong { color: #3f3942; font-size: 1.25rem; }
.riraa-health-ring small { color: #8c8590; font-size: 0.58rem; }

.riraa-capability-card {
    min-height: 18rem;
    border: 1px solid #ebe7ee;
    border-radius: 1rem;
    padding: 1.5rem;
    transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
}

.riraa-capability-card:hover {
    border-color: #d5cbd9;
    box-shadow: 0 16px 34px rgba(68, 39, 75, 0.08);
    transform: translateY(-4px);
}

@media (max-width: 640px) {
    .riraa-board-window { border-radius: 0.9rem 0.9rem 0 0; }
    .riraa-dark-product { box-shadow: 0 18px 35px rgba(0, 0, 0, 0.22); }
}

@media (prefers-reduced-motion: reduce) {
    .riraa-capability-card { transition: none; }
}
</style>
