import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import type {
    ProjectBoardSummary,
    ProjectContext,
    ProjectWorkspaceSummary,
} from '@/types';

type BoardPageProp = {
    id?: number;
    workspace?: { id?: number } | null;
};

export function useProjectContext() {
    const page = usePage();
    const context = computed<ProjectContext | null>(
        () => page.props.projectContext,
    );

    const url = computed(
        () =>
            new URL(
                page.url,
                typeof window === 'undefined'
                    ? 'http://localhost'
                    : window.location.origin,
            ),
    );

    const boardPageProp = computed(
        () => (page.props.board ?? null) as BoardPageProp | null,
    );

    const selectedWorkspace = computed<ProjectWorkspaceSummary | null>(() => {
        const workspaceId =
            boardPageProp.value?.workspace?.id ??
            Number(url.value.searchParams.get('workspace_id') ?? 0);

        return (
            context.value?.workspaces.find(
                (workspace) => workspace.id === workspaceId,
            ) ??
            context.value?.workspaces[0] ??
            null
        );
    });

    const selectedBoard = computed<ProjectBoardSummary | null>(() => {
        const boardId =
            boardPageProp.value?.id ??
            Number(url.value.searchParams.get('board_id') ?? 0);

        return (
            selectedWorkspace.value?.boards.find(
                (board) => board.id === boardId,
            ) ?? null
        );
    });

    const selectWorkspace = (workspaceId: number) => {
        router.visit(route('boards.index', { workspace_id: workspaceId }));
    };

    const selectBoard = (boardId: number) => {
        router.visit(route('boards.show', boardId));
    };

    return {
        context,
        selectedWorkspace,
        selectedBoard,
        selectWorkspace,
        selectBoard,
    };
}
