<?php

declare(strict_types=1);

/* الواجهة العامة الجديدة (v0.12.0, D-038) — ar. مسودة تحتاج إلى مراجعة من متحدث أصلي. */
return [
    'ui.brand.area' => 'بياتشنزا والمقاطعة',
    'ui.breadcrumb' => 'أنت هنا',

    'ui.footer.emergency' => 'في حالة الطوارئ: اتصل بالرقم 112',
    'ui.footer.explore' => 'استكشف',
    'ui.footer.information' => 'معلومات',
    'ui.footer.organizations' => 'للجهات والجمعيات',
    'ui.footer.map_credits' => 'الخرائط © OpenStreetMap · حدود البلديات: ISTAT (CC BY 4.0)',

    'ui.home.examples' => 'مثلاً:',
    'ui.home.town_title' => 'ابحث في بلديتك',
    'ui.home.town_text' => 'اختر البلدية التي تعيش فيها: سترى الخدمات التي يمكنك استخدامها.',
    'ui.home.town_label' => 'اختر بلديتك',
    'ui.home.town_submit' => 'اعرض على الخريطة',
    'ui.home.open_map' => 'افتح خريطة المقاطعة كاملة',
    'ui.home.needs_text' => 'اضغط على موضوع لترى الخدمات.',
    'ui.home.mediators_text' => 'أشخاص يساعدونك على التواصل مع المكاتب بلغتك.',
    'ui.home.mediators_cta' => 'ابحث عن وسيط',
    'ui.home.communities_text' => 'مجموعات وجمعيات الجاليات الأجنبية في بياتشنزا.',
    'ui.home.communities_cta' => 'اعرض الجمعيات',

    'ui.view.label' => 'طريقة العرض',
    'ui.view.list' => 'قائمة',
    'ui.view.map' => 'خريطة',

    'ui.filters.active' => 'المرشحات المفعّلة:',
    'ui.filters.remove' => '(إزالة المرشح)',
    'ui.filters.active_count' => 'مرشحات مفعّلة',
    'ui.filters.options' => 'خيارات أخرى',

    'ui.map.title' => 'خريطة الخدمات',
    'ui.map.intro' => 'اضغط على بلدية في الخريطة أو اخترها من القائمة: سترى فوراً الخدمات التي يمكنك استخدامها.',
    'ui.map.town_label' => 'بلديتك',
    'ui.map.town_hint' => 'تتحدّث النتائج فور اختيارك.',
    'ui.map.whole_province' => 'المقاطعة كاملة',
    'ui.map.boundaries_credit' => 'حدود البلديات ISTAT (CC BY 4.0)',
    'ui.map.step_free' => 'مقر بدون درج',
    'ui.map.town_count' => 'الخدمات: {count}',
    'ui.map.select_town' => 'اختر هذه البلدية',
    'ui.map.loading' => 'جارٍ تحديث النتائج…',
    'ui.map.updated' => '{town} {results}',
    'ui.map.help' => 'تعرض الخريطة البلديات وأماكن الخدمات. لاختيار بلدية باستخدام لوحة المفاتيح استعمل قائمة «بلديتك»؛ كل النتائج موجودة أيضاً في القائمة بعد الخريطة.',
    'ui.map.show_towns' => 'البلديات',
    'ui.map.show_points' => 'الأماكن',
    'ui.map.legend' => 'الخدمات حسب البلدية',
    'ui.map.town_reach' => '{count, plural, zero {لا توجد خدمات متاحة من {town} مع هذه المرشحات.} one {خدمة واحدة متاحة من {town}: مقرها في البلدية أو مفتوحة لسكانها.} two {خدمتان متاحتان من {town}: مقرهما في البلدية أو مفتوحتان لسكانها.} few {# خدمات متاحة من {town}: مقرها في البلدية أو مفتوحة لسكانها.} many {# خدمة متاحة من {town}: مقرها في البلدية أو مفتوحة لسكانها.} other {# خدمة متاحة من {town}: مقرها في البلدية أو مفتوحة لسكانها.}}',
    'ui.map.district_reach' => '{count, plural, zero {لا توجد خدمات في {town} مع هذه المرشحات.} one {خدمة واحدة في {town}.} two {خدمتان في {town}.} few {# خدمات في {town}.} many {# خدمة في {town}.} other {# خدمة في {town}.}}',
    'ui.map.widen' => 'وسّع البحث إلى كامل {district} ({count})',
    'ui.map.clear_town' => 'المقاطعة كاملة',
    'ui.map.no_results_hint' => 'جرّب اختيار المنطقة كاملة أو إزالة بعض المرشحات.',

    'ui.service.call' => 'اتصل',
    'ui.service.write' => 'اكتب',
    'ui.service.website' => 'الموقع الإلكتروني',
    'ui.service.where_title' => 'أين ومتى',
    'ui.service.contacts_title' => 'جهات الاتصال',
    'ui.service.category' => 'الموضوع',

    // v0.13.0 (D-039)
    'ui.map.keyboard_help' => 'عند تحديد الخريطة: + و − للتكبير والتصغير، والأسهم للتحرك.',
    'ui.communities.quick_title' => 'الجاليات الموجودة',
    'ui.communities.count' => '{count}',
    'ui.communities.more' => '{count, plural, zero {} one {وجالية أخرى} two {وجاليتان أخريان} few {و# جاليات أخرى} many {و# جالية أخرى} other {و# جالية أخرى}}',
    'ui.communities.join_title' => 'هل أنت عضو في جمعية؟',
    'ui.map.zoom_in' => 'تكبير الخريطة',
    'ui.map.zoom_out' => 'تصغير الخريطة',
    'ui.map.reset_view' => 'اعرض المقاطعة كاملة',
    'ui.map.fullscreen' => 'الخريطة بملء الشاشة',
    'ui.map.fullscreen_exit' => 'الخروج من ملء الشاشة',
    'ui.map.wheel_hint' => 'للتكبير استخدم Ctrl مع عجلة الفأرة، أو الزرين + و −، أو المس الخريطة',
];
