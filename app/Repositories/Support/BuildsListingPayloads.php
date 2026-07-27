<?php

namespace App\Repositories\Support;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait BuildsListingPayloads
{
    private const PER_PAGE_OPTIONS = [5, 10, 15, 25, 50];

    /**
     * @return array{search: string, per_page: int}
     */
    protected function filters(Request $request): array
    {
        $perPage = (int) $request->integer('per_page', 10);

        return [
            'search' => trim((string) $request->query('search', '')),
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10,
        ];
    }

    protected function idFilter(Request $request, string $key): ?int
    {
        $id = $request->integer($key);

        return $id > 0 ? $id : null;
    }

    /**
     * @return 'active'|'restricted'|'all'
     */
    protected function restrictionFilter(Request $request, bool $canRestrict): string
    {
        if (! $canRestrict) {
            return 'active';
        }

        $restriction = (string) $request->query('restriction', 'active');

        return in_array($restriction, ['active', 'restricted', 'all'], true)
            ? $restriction
            : 'active';
    }

    /**
     * @param  list<string>  $allowed
     * @return array{field: string, direction: 'asc'|'desc'}
     */
    protected function sort(Request $request, array $allowed, string $default, string $defaultDirection = 'asc'): array
    {
        $field = (string) $request->query('sort', $default);
        $requestedDirection = (string) $request->query('direction', $defaultDirection);
        $direction = $requestedDirection === 'desc' ? 'desc' : 'asc';

        return [
            'field' => in_array($field, $allowed, true) ? $field : $default,
            'direction' => $direction,
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @return array{data: array<int, mixed>, current_page: int, last_page: int, per_page: int, from: int|null, to: int|null, total: int, prev_page_url: string|null, next_page_url: string|null}
     */
    protected function paginatorPayload(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'next_page_url' => $paginator->nextPageUrl(),
        ];
    }

    /**
     * @return array{data: array<int, mixed>, current_page: int, last_page: int, per_page: int, from: int|null, to: int|null, total: int, prev_page_url: string|null, next_page_url: string|null}
     */
    protected function emptyPaginatorPayload(int $perPage): array
    {
        return $this->paginatorPayload(new LengthAwarePaginator([], 0, $perPage));
    }

    protected function date(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : null;
    }

    protected function roleName(string $role): string
    {
        return str($role)->replace('_', ' ')->headline()->toString();
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $columns
     */
    protected function searchColumns(Builder $query, string $search, array $columns): void
    {
        $firstColumn = array_shift($columns);

        if ($search === '' || $firstColumn === null) {
            return;
        }

        $query->where(function (Builder $query) use ($columns, $firstColumn, $search): void {
            $query->where($firstColumn, 'like', "%{$search}%");

            foreach ($columns as $column) {
                $query->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{field: string, direction: 'asc'|'desc'}  $sort
     * @param  array<string, string>  $columns
     */
    protected function orderByColumns(Builder $query, array $sort, array $columns): void
    {
        $query->orderBy($columns[$sort['field']] ?? $sort['field'], $sort['direction']);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  'active'|'restricted'|'all'  $restriction
     */
    protected function applyRestrictionFilter(Builder $query, string $table, string $restriction): void
    {
        if ($restriction === 'all') {
            return;
        }

        $query->where("{$table}.is_restricted", $restriction === 'restricted');
    }
}
