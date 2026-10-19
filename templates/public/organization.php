<?php
/**
 * Scheda organizzazione / associazione (vault "64", D-038).
 *
 * @var App\Core\View $this
 * @var array<string, mixed> $organization
 * @var array{communities: list<array<string, mixed>>, countries: list<string>} $profile
 * @var list<array<string, mixed>> $services
 */
$texts = $organization['texts'];
?>
<article>
    <header class="detail-head">
        <div class="container">
            <nav class="breadcrumb" aria-label="<?= $this->e($this->t('ui.breadcrumb')) ?>">
                <ol>
                    <li><a href="<?= $this->e($this->route('public.home')) ?>"><?= $this->e($this->t('nav.home')) ?></a></li>
                    <li><span aria-current="page"><?= $this->e($organization['name']) ?></span></li>
                </ol>
            </nav>
            <ul class="badges">
                <li class="badge badge--primary"><?= $this->icon('building-2') ?><?= $this->localized($organization['type']) ?></li>
                <?php if ($organization['is_community_based']): ?>
                    <li class="badge"><?= $this->icon('users-round') ?><?= $this->e($this->t('catalog.organization.community_based')) ?></li>
                <?php endif; ?>
                <?php foreach ($profile['communities'] as $community): ?>
                    <li class="badge"><?= $this->localized($community['name']) ?></li>
                <?php endforeach; ?>
            </ul>
            <h1><?= $this->e($organization['name']) ?></h1>
            <?php if ($organization['contacts'] !== []): ?>
                <?= $this->partial('contact-list', ['contacts' => $organization['contacts']]) ?>
            <?php endif; ?>
        </div>
    </header>

    <div class="container detail-layout">
        <div class="detail-layout__main">
            <?php if ($texts['description'] !== null): ?>
                <div class="rich-text service-section"><?= $this->richText($texts['description']) ?></div>
            <?php endif; ?>

            <?php if ($texts['activities'] !== null): ?>
                <section class="service-section">
                    <h2><?= $this->icon('heart-handshake') ?><?= $this->e($this->t('communities.activities')) ?></h2>
                    <div class="rich-text"><?= $this->richText($texts['activities']) ?></div>
                </section>
            <?php endif; ?>
            <?php if ($texts['participation_info'] !== null): ?>
                <section class="service-section">
                    <h2><?= $this->icon('handshake') ?><?= $this->e($this->t('communities.participation')) ?></h2>
                    <div class="rich-text"><?= $this->richText($texts['participation_info']) ?></div>
                </section>
            <?php endif; ?>

            <?php if ($profile['countries'] !== [] || $organization['languages'] !== []): ?>
                <dl class="facts">
                    <?php if ($profile['countries'] !== []): ?>
                        <dt><?= $this->e($this->t('communities.countries')) ?></dt>
                        <dd><?= $this->e(implode(', ', array_map($this->countryName(...), $profile['countries']))) ?></dd>
                    <?php endif; ?>
                    <?php if ($organization['languages'] !== []): ?>
                        <dt><?= $this->e($this->t('catalog.organization.languages')) ?></dt>
                        <dd><?= $this->e(implode(', ', array_map($this->languageName(...), $organization['languages']))) ?></dd>
                    <?php endif; ?>
                </dl>
            <?php endif; ?>

            <?php if ($services !== []): ?>
                <section class="service-section">
                    <h2><?= $this->icon('list') ?><?= $this->e($this->t('catalog.organization.services')) ?></h2>
                    <ul class="card-list">
                        <?php foreach ($services as $service): ?>
                            <li><?= $this->partial('service-card', ['service' => $service]) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </div>

        <?php if ($organization['sites'] !== []): ?>
            <aside class="detail-layout__aside" aria-labelledby="org-sites-title">
                <h2 id="org-sites-title"><?= $this->e($this->t('catalog.organization.sites')) ?></h2>
                <?php foreach ($organization['sites'] as $site): ?>
                    <?= $this->partial('site', ['site' => $site]) ?>
                <?php endforeach; ?>
            </aside>
        <?php endif; ?>
    </div>
</article>
