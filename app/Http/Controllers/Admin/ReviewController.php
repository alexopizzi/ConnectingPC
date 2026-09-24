<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Management\ReviewService;
use App\Http\Controllers\Manage\ManagementController;
use App\Http\Request;
use App\Http\Response;

/** Coda di revisione (RF-35): contenuti in revisione e traduzioni da revisionare. Permessi in ReviewService. */
final class ReviewController extends ManagementController
{
    protected function area(): string
    {
        return 'admin';
    }

    public function index(Request $request): Response
    {
        $reviews = $this->container->get(ReviewService::class);

        return $this->page('admin/tools/review', [
            'pageTitle' => $this->t('admin.review.title'),
            'content' => $reviews->pendingContent(),
            'translations' => $reviews->pendingTranslations(),
        ]);
    }

    public function decide(Request $request): Response
    {
        return $this->attempt(
            fn () => $this->container->get(ReviewService::class)->decide(
                $this->user($request),
                (string) $request->attribute('type'),
                (int) $request->attribute('id'),
                $request->string('decision'),
                $request->string('note'),
            ),
            'manage.saved', 'review.index',
        );
    }

    public function approveTranslation(Request $request): Response
    {
        return $this->attempt(
            fn () => $this->container->get(ReviewService::class)->approveTranslation(
                $this->user($request),
                (string) $request->attribute('type'),
                (int) $request->attribute('id'),
                $request->string('locale'),
            ),
            'manage.saved', 'review.index',
        );
    }
}
