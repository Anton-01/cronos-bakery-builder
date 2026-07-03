<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Application\Actions\PublishContentAction;
use App\Modules\CMS\Application\Actions\RollbackContentAction;
use App\Modules\CMS\Domain\Models\ContentVersion;
use App\Modules\CMS\Domain\Models\ContentWorkflow;
use App\Modules\CMS\Domain\Models\Page;
use App\Modules\CMS\Presentation\Http\Requests\RollbackVersionRequest;
use App\Modules\CMS\Presentation\Http\Requests\SchedulePublicationRequest;
use App\Modules\CMS\Presentation\Http\Requests\WorkflowActionRequest;
use App\Modules\CMS\Presentation\Http\Resources\ContentVersionResource;
use App\Modules\CMS\Presentation\Http\Resources\ContentWorkflowResource;
use App\Modules\CMS\Presentation\Http\Resources\PageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * Editorial workflow of a page: review/approval transitions, version history
 * and rollback. Every transition snapshots the page state into
 * `content_versions` and appends an entry to `content_workflows`
 * (see PublishContentAction), so the history endpoints simply read those.
 *
 * Draft saving itself lives in PageController/PageBlockController: saving
 * content never changes the publication status.
 */
class ContentWorkflowController extends Controller
{
    public function __construct(
        private readonly PublishContentAction $publishAction,
        private readonly RollbackContentAction $rollbackAction,
    ) {
    }

    public function submitReview(WorkflowActionRequest $request, int $page): JsonResponse
    {
        return $this->workflowResponse($this->transition(
            fn (): ContentWorkflow => $this->publishAction->submitForReview(
                $this->page($page),
                $this->actorId($request),
                $request->commentText(),
            ),
        ));
    }

    public function approve(WorkflowActionRequest $request, int $page): JsonResponse
    {
        return $this->workflowResponse($this->transition(
            fn (): ContentWorkflow => $this->publishAction->approve(
                $this->page($page),
                $this->actorId($request),
                $request->commentText(),
            ),
        ));
    }

    public function reject(WorkflowActionRequest $request, int $page): JsonResponse
    {
        return $this->workflowResponse($this->transition(
            fn (): ContentWorkflow => $this->publishAction->reject(
                $this->page($page),
                $this->actorId($request),
                $request->commentText(),
            ),
        ));
    }

    public function schedule(SchedulePublicationRequest $request, int $page): JsonResponse
    {
        return $this->workflowResponse($this->transition(
            fn (): ContentWorkflow => $this->publishAction->schedule(
                $this->page($page),
                new \DateTimeImmutable((string) $request->validated('publish_at')),
                $this->actorId($request),
            ),
        ));
    }

    /** Version history (most recent first). */
    public function versions(int $page): AnonymousResourceCollection
    {
        $versions = ContentVersion::query()
            ->with('author')
            ->where('versionable_type', $this->page($page)->getMorphClass())
            ->where('versionable_id', $page)
            ->orderByDesc('version_number')
            ->get();

        return ContentVersionResource::collection($versions);
    }

    /** Restore the page to the state captured by a specific version. */
    public function rollback(RollbackVersionRequest $request, int $page): PageResource
    {
        $restored = $this->rollbackAction->execute(
            $this->page($page),
            (int) $request->validated('version_id'),
            $this->actorId($request),
        );

        return new PageResource($restored->load(['blocks.section', 'brand']));
    }

    /** Workflow transition history (most recent first). */
    public function workflows(int $page): AnonymousResourceCollection
    {
        $workflows = ContentWorkflow::query()
            ->with(['requester', 'approver'])
            ->where('workflowable_type', $this->page($page)->getMorphClass())
            ->where('workflowable_id', $page)
            ->orderByDesc('created_at')
            ->get();

        return ContentWorkflowResource::collection($workflows);
    }

    /**
     * Run a workflow transition, translating domain rule violations (illegal
     * status transitions, missing approver) into a 422 instead of a 500.
     */
    private function transition(\Closure $callback): ContentWorkflow
    {
        try {
            return $callback();
        } catch (\DomainException $exception) {
            abort(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }
    }

    private function page(int $id): Page
    {
        return Page::query()->findOrFail($id);
    }

    private function actorId(Request $request): int
    {
        return (int) $request->user()->getKey();
    }

    private function workflowResponse(ContentWorkflow $workflow): JsonResponse
    {
        return (new ContentWorkflowResource($workflow->load(['requester', 'approver'])))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}
