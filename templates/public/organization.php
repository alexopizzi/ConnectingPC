<?php
/**
 * @var App\Core\View $this
 * @var array<string, mixed> $organization
 * @var array{communities: list<array<string, mixed>>, countries: list<string>} $profile
 * @var list<array<string, mixed>> $services
 */
$texts = $organization['texts'];
?>
<article class="container section">
    <p class="breadcrumb"><a href="<?= $this->e($this->route('public.home')) ?>"><?= $this->e($this->t('nav.home')) ?></a></p>
    <h1><?= $this->e($organization['name']) ?></h1>
    <ul class="badges">
        <li class="badge"><?= $this->localized($organization['type']) ?></li>
        <?php if ($organization['is_community_based']): ?>
            <li class="badge"><span aria-hidden="true">👥</span> <?= $this->e($this->t('catalog.organization.community_based')) ?></li>
        <?php endif; ?>
        <?php foreach ($profile['communities'] as $community): ?>
            <li class="badge"><?= $this->localized($community['name']) ?></li>
        <?php endforeach; ?>
    </ul>

    <?= $this->richText($texts['description']) ?>

    <?php if ($texts['activities'] !== null): ?>
        <h2><?= $this->e($this->t('communities.activities')) ?></h2>
        <?= $this->richText($texts['activities']) ?>
    <?php endif; ?>
    <?php if ($texts['participation_info'] !== null): ?>
        <h2><?= $this->e($this->t('communities.participation')) ?></h2>
        <?= $this->richText($texts['participation_info']) ?>
    <?php endif; ?>

    <?php if ($profile['countries'] !== []): ?>
        <p><strong><?= $this->e($this->t('communities.countries')) ?>:</strong>
            <?= $this->e(implode(', ', array_map($this->countryName(...), $profile['countries']))) ?></p>
    <?php endif; ?>

    <?php if ($organization['languages'] !== []): ?>
        <p><strong><?= $this->e($this->t('catalog.organization.languages')) ?>:</strong>
            <?= $this->e(implode(', ', array_map($this->languageName(...), $organization['languages']))) ?></p>
    <?php endif; ?>

    <?= $this->partial('contact-list', ['contacts' => $organization['contacts']]) ?>

    <?php if ($services !== []): ?>
        <section class="service-section">
            <h2><?= $this->e($this->t('catalog.organization.services')) ?></h2>
            <ul class="card-list">
                <?php foreach ($services as $service): ?>
                    <li><?= $this->partial('service-card', ['service' => $service]) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($organization['sites'] !== []): ?>
        <section class="service-section">
            <h2><?= $this->e($this->t('catalog.organization.sites')) ?></h2>
            <?php foreach ($organization['sites'] as $site): ?>
                <?= $this->partial('site', ['site' => $site]) ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</article>
