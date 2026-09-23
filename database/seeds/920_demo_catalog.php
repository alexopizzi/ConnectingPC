<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Container;
use App\Core\Database;
use App\Support\Slug;

/*
 * CATALOGO DIMOSTRATIVO FITTIZIO (solo `seed --demo`, mai in produzione).
 * Enti, indirizzi, telefoni ed email sono INVENTATI: nomi con "Esempio", numeri 0523 000 xxx,
 * domini example.org. Servono solo a provare il sito. Le coordinate sono vicine ai centri dei comuni.
 * Testi completi in italiano; in inglese, francese e arabo nome e sintesi (il resto usa il ripiego
 * sull'italiano, così si vede anche il funzionamento del fallback).
 */
return static function (Container $c): void {
    if (App::environment() === 'production') {
        throw new RuntimeException('I dati dimostrativi non si caricano in produzione.');
    }

    // Orari: [giorno da, giorno a, apre, chiude, su appuntamento]
    $weekMornings = [[1, 5, '09:00', '12:30', 0]];
    $organizations = [
        'ponte' => [
            'name' => 'Associazione Esempio Ponte', 'type' => 'community_association', 'community' => 1,
            'languages' => ['it', 'ar', 'fr', 'wo'],
            'description' => 'Associazione di comunità fondata da cittadine e cittadini di origine africana e nordafricana. Offre orientamento, corsi e momenti di incontro.',
            'sites' => [
                'sede' => ['Sede dell’associazione', "Via dell'Esempio 12", '29121', 'Piacenza', 45.049020, 9.696120, 'yes', [[2, 2, '15:00', '19:00', 0], [4, 4, '15:00', '19:00', 0], [6, 6, '10:00', '12:00', 0]], '+39 0523 000 101', 'ponte@example.org', 'Autobus 1 e 3, fermata Piazza Esempio. Citofono “Ponte”.'],
            ],
            'website' => 'https://example.org/ponte',
        ],
        'orizzonti' => [
            'name' => 'Cooperativa Esempio Orizzonti', 'type' => 'cooperative', 'community' => 0,
            'languages' => ['it', 'en', 'es', 'sq'],
            'description' => 'Cooperativa sociale che accompagna le persone nella ricerca di lavoro e di casa.',
            'sites' => [
                'piacenza' => ['Sportello di Piacenza', 'Viale Esempio 45', '29122', 'Piacenza', 45.055110, 9.688540, 'yes', [[1, 1, '09:00', '13:00', 0], [3, 3, '14:00', '18:00', 0], [5, 5, '09:00', '13:00', 0]], '+39 0523 000 201', 'lavoro@example.org', null],
                'fiorenzuola' => ['Sportello di Fiorenzuola', 'Corso Esempio 8', '29017', "Fiorenzuola d'Arda", 44.927310, 9.911280, 'partial', [[2, 2, '09:00', '12:00', 1]], '+39 0523 000 202', null, 'Vicino alla stazione dei treni.'],
            ],
            'website' => 'https://example.org/orizzonti',
        ],
        'sospeso' => [
            'name' => 'Centro Esempio Sospeso', 'type' => 'cultural_centre', 'community' => 1,
            'languages' => ['it', 'uk', 'ru'],
            'description' => 'Centro culturale (organizzazione dimostrativa con accesso sospeso alla piattaforma).',
            'sites' => [
                'sede' => ['Centro culturale', 'Via Esempio Nord 3', '29015', 'Castel San Giovanni', 45.059420, 9.434150, 'unknown', [[6, 6, '15:00', '18:00', 0]], '+39 0523 000 301', null, null],
            ],
            'website' => null,
        ],
        'sportello_immigrazione' => [
            'name' => 'Sportello Immigrazione Esempio', 'type' => 'public_service', 'community' => 0,
            'languages' => ['it', 'en', 'fr', 'ar'],
            'description' => 'Ufficio pubblico di informazione sulle pratiche di soggiorno, cittadinanza e ricongiungimento.',
            'sites' => [
                'centro' => ['Sede centrale', 'Piazza Esempio 1', '29121', 'Piacenza', 45.052210, 9.692880, 'yes', [[1, 5, '08:30', '12:30', 0], [2, 2, '14:30', '16:30', 1]], '+39 0523 000 401', 'immigrazione@example.org', 'Piano terra, ingresso accessibile dal cortile.'],
            ],
            'website' => 'https://example.org/immigrazione',
        ],
        'impiego' => [
            'name' => 'Centro per l’Impiego Esempio', 'type' => 'public_service', 'community' => 0,
            'languages' => ['it', 'en'],
            'description' => 'Servizio pubblico per il lavoro: iscrizione, offerte, orientamento e tirocini.',
            'sites' => [
                'piacenza' => ['Sede di Piacenza', 'Via Esempio del Lavoro 20', '29122', 'Piacenza', 45.046870, 9.701930, 'yes', $weekMornings, '+39 0523 000 501', 'impiego@example.org', null],
                'castello' => ['Sede di Castel San Giovanni', 'Via Esempio 5', '29015', 'Castel San Giovanni', 45.058100, 9.436900, 'yes', [[1, 1, '09:00', '12:30', 0], [4, 4, '09:00', '12:30', 0]], '+39 0523 000 502', null, null],
            ],
            'website' => 'https://example.org/impiego',
        ],
        'comune' => [
            'name' => 'Comune Esempio – Sportello al cittadino', 'type' => 'municipality', 'community' => 0,
            'languages' => ['it'],
            'description' => 'Uffici comunali dimostrativi: anagrafe, residenza e servizi sociali.',
            'sites' => [
                'anagrafe' => ['Anagrafe', 'Via Esempio Comunale 2', '29121', 'Piacenza', 45.051340, 9.694770, 'yes', [[1, 5, '08:30', '12:30', 1], [4, 4, '14:30', '17:30', 1]], '+39 0523 000 601', 'anagrafe@example.org', null],
                'sociali' => ['Servizi sociali', 'Via Esempio Sociale 9', '29121', 'Piacenza', 45.050050, 9.698400, 'yes', [[1, 5, '09:00', '12:00', 1]], '+39 0523 000 602', 'sociali@example.org', null],
            ],
            'website' => 'https://example.org/comune',
        ],
        'consultorio' => [
            'name' => 'Consultorio Familiare Esempio', 'type' => 'health_authority', 'community' => 0,
            'languages' => ['it', 'en', 'ar'],
            'description' => 'Servizio sanitario dimostrativo per donne, coppie, famiglie e giovani.',
            'sites' => [
                'piacenza' => ['Consultorio di Piacenza', 'Via Esempio della Salute 30', '29121', 'Piacenza', 45.044980, 9.689410, 'yes', [[1, 5, '08:30', '13:00', 0], [2, 2, '14:00', '17:00', 0]], '+39 0523 000 701', null, null],
                'bobbio' => ['Consultorio di Bobbio', 'Piazza Esempio 4', '29022', 'Bobbio', 44.771250, 9.386410, 'partial', [[3, 3, '09:00', '12:00', 1]], '+39 0523 000 702', null, 'In montagna: controllare gli orari degli autobus.'],
            ],
            'website' => null,
        ],
        'ambulatorio' => [
            'name' => 'Ambulatorio Salute per Tutti Esempio', 'type' => 'health_authority', 'community' => 0,
            'languages' => ['it', 'en', 'fr', 'ar'],
            'description' => 'Ambulatorio dimostrativo per persone senza medico di base.',
            'sites' => [
                'sede' => ['Ambulatorio', 'Via Esempio Ospedale 15', '29121', 'Piacenza', 45.047510, 9.683760, 'yes', [[1, 1, '14:00', '17:00', 0], [3, 3, '14:00', '17:00', 0], [5, 5, '09:00', '12:00', 0]], '+39 0523 000 801', null, null],
            ],
            'website' => null,
        ],
        'cpia' => [
            'name' => 'Centro per l’Istruzione degli Adulti Esempio', 'type' => 'school', 'community' => 0,
            'languages' => ['it', 'en', 'fr'],
            'description' => 'Scuola pubblica dimostrativa per adulti: corsi di italiano e titoli di studio.',
            'sites' => [
                'piacenza' => ['Sede di Piacenza', 'Via Esempio della Scuola 7', '29122', 'Piacenza', 45.057030, 9.700520, 'yes', [[1, 5, '09:00', '12:00', 0], [1, 4, '18:00', '21:00', 0]], '+39 0523 000 901', 'segreteria.cpia@example.org', null],
                'fiorenzuola' => ['Sede di Fiorenzuola', 'Via Esempio 22', '29017', "Fiorenzuola d'Arda", 44.925900, 9.908100, 'yes', [[2, 2, '18:00', '21:00', 0], [4, 4, '18:00', '21:00', 0]], '+39 0523 000 902', null, null],
            ],
            'website' => 'https://example.org/cpia',
        ],
        'patronato' => [
            'name' => 'Patronato Esempio', 'type' => 'patronage', 'community' => 0,
            'languages' => ['it', 'ar', 'sq', 'ro'],
            'description' => 'Patronato dimostrativo: ISEE, pratiche previdenziali, compilazione del kit per il permesso di soggiorno.',
            'sites' => [
                'piacenza' => ['Ufficio di Piacenza', 'Via Esempio Sindacale 11', '29121', 'Piacenza', 45.053870, 9.697330, 'no', [[1, 5, '09:00', '12:30', 1], [1, 4, '15:00', '18:00', 1]], '+39 0523 000 111', 'patronato@example.org', 'Primo piano senza ascensore: su richiesta si riceve al piano terra.'],
                'podenzano' => ['Ufficio di Podenzano', 'Via Esempio 3', '29027', 'Podenzano', 44.955010, 9.684120, 'yes', [[3, 3, '09:00', '12:00', 1]], '+39 0523 000 112', null, null],
            ],
            'website' => 'https://example.org/patronato',
        ],
        'legale' => [
            'name' => 'Sportello Legale Solidale Esempio', 'type' => 'volunteer_association', 'community' => 0,
            'languages' => ['it', 'en', 'fr'],
            'description' => 'Avvocate e avvocati volontari offrono una prima consulenza gratuita.',
            'sites' => [
                'sede' => ['Sportello', 'Via Esempio dei Diritti 6', '29121', 'Piacenza', 45.050930, 9.690260, 'yes', [[2, 2, '17:00', '19:30', 1], [5, 5, '17:00', '19:30', 1]], '+39 0523 000 121', 'legale@example.org', null],
            ],
            'website' => null,
        ],
        'antiviolenza' => [
            'name' => 'Centro Antiviolenza Esempio', 'type' => 'third_sector', 'community' => 0,
            'languages' => ['it', 'en', 'fr', 'ar', 'es'],
            'description' => 'Centro dimostrativo di ascolto e protezione per donne che subiscono violenza.',
            'sites' => [
                'sede' => ['Sede di ascolto', 'Indirizzo riservato (Piacenza)', '29121', 'Piacenza', null, null, 'yes', [[1, 5, '09:00', '18:00', 0]], '+39 0523 000 131', null, 'Per sicurezza l’indirizzo si comunica al telefono.'],
            ],
            'website' => null,
            'hidden_site' => true,
        ],
        'emporio' => [
            'name' => 'Emporio Solidale Esempio', 'type' => 'volunteer_association', 'community' => 0,
            'languages' => ['it', 'ar', 'ro'],
            'description' => 'Distribuzione dimostrativa di alimenti e vestiti per famiglie in difficoltà, su invio dei servizi sociali.',
            'sites' => [
                'piacenza' => ['Emporio di Piacenza', 'Via Esempio Solidale 18', '29122', 'Piacenza', 45.058720, 9.705180, 'yes', [[2, 2, '15:00', '18:00', 0], [6, 6, '09:00', '12:00', 0]], '+39 0523 000 141', null, null],
                'rottofreno' => ['Punto di Rottofreno', 'Via Esempio 1', '29010', 'Rottofreno', 45.057990, 9.548760, 'partial', [[4, 4, '15:00', '17:00', 0]], '+39 0523 000 142', null, null],
            ],
            'website' => null,
        ],
        'mediazione' => [
            'name' => 'Rete Mediatori Esempio', 'type' => 'association', 'community' => 1,
            'languages' => ['it', 'ar', 'fr', 'en', 'ur', 'pa', 'zh', 'uk', 'ti'],
            'description' => 'Rete dimostrativa di mediatrici e mediatori linguistico-culturali per enti e cittadini.',
            'sites' => [
                'sede' => ['Segreteria', 'Via Esempio delle Lingue 10', '29121', 'Piacenza', 45.048500, 9.693400, 'yes', [[1, 5, '09:00', '13:00', 0]], '+39 0523 000 151', 'mediazione@example.org', null],
            ],
            'website' => 'https://example.org/mediazione',
        ],
    ];

    $services = [
        [
            'org' => 'sportello_immigrazione', 'sites' => ['centro'], 'category' => 'residence_permit', 'categories' => ['residence_permit'],
            'needs' => ['documents' => 10], 'access' => 'in_person,phone', 'booking' => 'required', 'cost' => 'free', 'mediation' => 'available',
            'languages' => [['en', 'staff'], ['fr', 'staff'], ['ar', 'mediator']],
            'it' => [
                'name' => 'Informazioni sul rinnovo del permesso di soggiorno',
                'summary' => 'Ti spieghiamo come rinnovare il permesso di soggiorno e quali documenti preparare.',
                'description' => "Lo sportello dà informazioni sul primo rilascio e sul rinnovo del permesso di soggiorno.\nTi aiuta a capire quale tipo di permesso chiedere e come compilare il kit postale.",
                'target_audience' => 'Persone straniere che vivono a Piacenza e provincia.',
                'requirements' => 'Avere un permesso di soggiorno in scadenza o scaduto da meno di 60 giorni.',
                'documents' => "- Passaporto\n- Permesso di soggiorno attuale\n- Codice fiscale\n- Documenti sul lavoro o sul reddito",
                'access_info' => 'Prendi un appuntamento per telefono. Il giorno dell’appuntamento arriva 10 minuti prima.',
                'booking_info' => 'Telefona dal lunedì al venerdì dalle 9 alle 12.',
                'cost_info' => 'Il servizio di informazione è gratuito. Il kit postale e le marche da bollo si pagano a parte.',
                'keywords' => 'permesso soggiorno rinnovo pds kit postale questura',
            ],
            'en' => ['name' => 'Information on renewing your residence permit', 'summary' => 'We explain how to renew your residence permit and which documents to prepare.'],
            'fr' => ['name' => 'Informations sur le renouvellement du titre de séjour', 'summary' => 'Nous vous expliquons comment renouveler votre titre de séjour et quels papiers préparer.'],
            'ar' => ['name' => 'معلومات حول تجديد تصريح الإقامة', 'summary' => 'نشرح لك كيفية تجديد تصريح الإقامة والوثائق التي يجب تحضيرها.'],
        ],
        [
            'org' => 'sportello_immigrazione', 'sites' => ['centro'], 'category' => 'family_reunification', 'categories' => ['family_reunification', 'citizenship'],
            'needs' => ['documents' => 8], 'access' => 'in_person,email', 'booking' => 'required', 'cost' => 'free', 'mediation' => 'on_request',
            'languages' => [['en', 'staff'], ['fr', 'staff']],
            'it' => [
                'name' => 'Ricongiungimento familiare e cittadinanza',
                'summary' => 'Informazioni per far venire in Italia i tuoi familiari o per chiedere la cittadinanza italiana.',
                'description' => 'Lo sportello spiega i requisiti e i passaggi della domanda di ricongiungimento familiare e della domanda di cittadinanza.',
                'requirements' => 'Permesso di soggiorno valido. Per la cittadinanza: residenza in Italia da almeno 10 anni (regole generali).',
                'documents' => "- Passaporto\n- Permesso di soggiorno\n- Certificato di residenza\n- Documenti sul reddito e sull’alloggio",
                'access_info' => 'Solo su appuntamento, anche via email.',
                'cost_info' => 'Informazioni gratuite.',
                'keywords' => 'ricongiungimento familiari cittadinanza nulla osta',
            ],
            'en' => ['name' => 'Family reunification and citizenship', 'summary' => 'Information to bring your family to Italy or to apply for Italian citizenship.'],
            'fr' => ['name' => 'Regroupement familial et nationalité', 'summary' => 'Informations pour faire venir votre famille en Italie ou demander la nationalité italienne.'],
            'ar' => ['name' => 'لمّ شمل الأسرة والجنسية', 'summary' => 'معلومات لاستقدام أفراد أسرتك إلى إيطاليا أو لطلب الجنسية الإيطالية.'],
        ],
        [
            'org' => 'patronato', 'sites' => ['piacenza', 'podenzano'], 'category' => 'caf', 'categories' => ['caf', 'residence_permit'],
            'needs' => ['documents' => 7, 'admin_procedures' => 9], 'access' => 'in_person', 'booking' => 'recommended', 'cost' => 'partly_free', 'mediation' => 'not_available',
            'languages' => [['ar', 'staff'], ['sq', 'staff'], ['ro', 'staff']],
            'it' => [
                'name' => 'Compilazione del kit per il permesso di soggiorno e pratiche ISEE',
                'summary' => 'Ti aiutiamo a compilare i moduli del permesso di soggiorno e a fare l’ISEE.',
                'description' => 'Operatori del patronato compilano con te il kit postale per il permesso di soggiorno, la domanda di ISEE e di assegno unico.',
                'documents' => "- Documento d’identità\n- Codice fiscale\n- Permesso di soggiorno\n- Contratto di affitto e buste paga",
                'access_info' => 'Meglio prendere appuntamento. Porta tutti i documenti in originale.',
                'cost_info' => 'ISEE gratuito. Per alcune pratiche è richiesto un contributo.',
                'keywords' => 'kit postale isee assegno unico patronato bonus',
            ],
            'en' => ['name' => 'Help with residence permit forms and ISEE', 'summary' => 'We help you fill in the residence permit forms and get your ISEE.'],
            'fr' => ['name' => 'Aide pour le dossier de titre de séjour et l’ISEE', 'summary' => 'Nous vous aidons à remplir les formulaires du titre de séjour et à faire l’ISEE.'],
            'ar' => ['name' => 'المساعدة في ملء ملف تصريح الإقامة ووثيقة ISEE', 'summary' => 'نساعدك في ملء استمارات تصريح الإقامة واستخراج وثيقة ISEE.'],
        ],
        [
            'org' => 'impiego', 'sites' => ['piacenza', 'castello'], 'category' => 'job_centres', 'categories' => ['job_centres'],
            'needs' => ['work' => 10], 'access' => 'in_person,online', 'booking' => 'recommended', 'cost' => 'free', 'mediation' => 'on_request',
            'languages' => [['en', 'staff']], 'online_url' => 'https://example.org/impiego/offerte',
            'it' => [
                'name' => 'Iscrizione al centro per l’impiego e offerte di lavoro',
                'summary' => 'Iscriviti come persona in cerca di lavoro e consulta le offerte.',
                'description' => 'Puoi rilasciare la dichiarazione di disponibilità al lavoro, consultare le offerte, fare un colloquio di orientamento e partecipare a tirocini.',
                'target_audience' => 'Persone disoccupate o in cerca di un nuovo lavoro, con permesso di soggiorno che consente di lavorare.',
                'documents' => "- Documento d’identità\n- Permesso di soggiorno\n- Codice fiscale\n- Curriculum, se lo hai",
                'access_info' => 'Puoi iniziare online e poi fissare un colloquio in sede.',
                'cost_info' => 'Gratuito.',
                'keywords' => 'lavoro disoccupazione did offerte tirocinio impiego',
            ],
            'en' => ['name' => 'Job centre registration and job offers', 'summary' => 'Register as a jobseeker and look at the job offers.'],
            'fr' => ['name' => 'Inscription au centre pour l’emploi et offres d’emploi', 'summary' => 'Inscrivez-vous comme demandeur d’emploi et consultez les offres.'],
            'ar' => ['name' => 'التسجيل في مركز التوظيف وعروض العمل', 'summary' => 'سجّل كباحث عن عمل واطّلع على عروض العمل.'],
        ],
        [
            'org' => 'orizzonti', 'sites' => ['piacenza', 'fiorenzuola'], 'category' => 'job_orientation', 'categories' => ['job_orientation', 'work_training'],
            'needs' => ['work' => 9, 'training' => 6], 'access' => 'in_person,phone', 'booking' => 'required', 'cost' => 'free', 'mediation' => 'available',
            'languages' => [['en', 'staff'], ['es', 'staff'], ['sq', 'staff'], ['ar', 'mediator']],
            'it' => [
                'name' => 'Aiuto per il curriculum e la ricerca di lavoro',
                'summary' => 'Scriviamo insieme il tuo curriculum e ti prepariamo al colloquio di lavoro.',
                'description' => 'Colloqui individuali per scrivere il curriculum, cercare offerte online e prepararsi ai colloqui. Informazioni sui corsi di formazione professionale gratuiti.',
                'access_info' => 'Su appuntamento. A Fiorenzuola solo il martedì mattina.',
                'cost_info' => 'Gratuito.',
                'keywords' => 'curriculum cv colloquio lavoro formazione corso',
            ],
            'en' => ['name' => 'Help with your CV and job search', 'summary' => 'We write your CV together and prepare you for job interviews.'],
            'fr' => ['name' => 'Aide pour le CV et la recherche d’emploi', 'summary' => 'Nous rédigeons ensemble votre CV et vous préparons aux entretiens.'],
            'ar' => ['name' => 'المساعدة في السيرة الذاتية والبحث عن عمل', 'summary' => 'نكتب معك سيرتك الذاتية ونحضّرك لمقابلة العمل.'],
        ],
        [
            'org' => 'orizzonti', 'sites' => ['piacenza'], 'category' => 'housing_help_desk', 'categories' => ['housing_help_desk'],
            'needs' => ['housing' => 10], 'access' => 'in_person,phone', 'booking' => 'required', 'cost' => 'free', 'mediation' => 'available',
            'languages' => [['en', 'staff'], ['es', 'staff'], ['ar', 'mediator']],
            'it' => [
                'name' => 'Sportello casa: ricerca di un alloggio in affitto',
                'summary' => 'Ti aiutiamo a cercare una casa in affitto e a capire il contratto.',
                'description' => 'Consulenza su annunci, contratti di affitto, deposito cauzionale e contributi per l’affitto. Mediazione con i proprietari.',
                'requirements' => 'Avere un reddito o un’offerta di lavoro, anche a tempo determinato.',
                'access_info' => 'Solo su appuntamento.',
                'cost_info' => 'Gratuito.',
                'keywords' => 'casa affitto alloggio contratto proprietario',
            ],
            'en' => ['name' => 'Housing desk: finding a flat to rent', 'summary' => 'We help you look for a flat to rent and understand the contract.'],
            'fr' => ['name' => 'Guichet logement : trouver un logement à louer', 'summary' => 'Nous vous aidons à chercher un logement et à comprendre le contrat.'],
            'ar' => ['name' => 'مكتب السكن: البحث عن شقة للإيجار', 'summary' => 'نساعدك في البحث عن سكن للإيجار وفهم العقد.'],
        ],
        [
            'org' => 'comune', 'sites' => ['sociali'], 'category' => 'personal_services', 'categories' => ['personal_services', 'housing_emergency'],
            'needs' => ['housing' => 8, 'social_support' => 9], 'access' => 'in_person,phone', 'booking' => 'required', 'cost' => 'free', 'mediation' => 'on_request',
            'languages' => [],
            'it' => [
                'name' => 'Servizi sociali: colloquio con l’assistente sociale',
                'summary' => 'Se hai problemi economici, di casa o in famiglia, puoi parlare con un’assistente sociale.',
                'description' => 'L’assistente sociale ascolta la tua situazione e ti orienta verso aiuti economici, accoglienza in emergenza, sostegno alla famiglia.',
                'target_audience' => 'Residenti nel Comune di Piacenza.',
                'requirements' => 'Residenza nel Comune (per le emergenze si valuta caso per caso).',
                'access_info' => 'Telefona per fissare un colloquio. Se non parli italiano puoi chiedere un mediatore.',
                'cost_info' => 'Gratuito.',
                'keywords' => 'assistente sociale aiuto economico emergenza casa dormitorio',
            ],
            'en' => ['name' => 'Social services: meeting a social worker', 'summary' => 'If you have money, housing or family problems, you can talk to a social worker.'],
            'fr' => ['name' => 'Services sociaux : rendez-vous avec une assistante sociale', 'summary' => 'En cas de difficultés d’argent, de logement ou familiales, vous pouvez parler avec une assistante sociale.'],
            'ar' => ['name' => 'الخدمات الاجتماعية: لقاء مع مساعد اجتماعي', 'summary' => 'إذا كانت لديك مشاكل مالية أو في السكن أو في الأسرة يمكنك التحدث مع مساعد اجتماعي.'],
            'territories' => ['Piacenza'],
        ],
        [
            'org' => 'comune', 'sites' => ['anagrafe'], 'category' => 'registry_residence', 'categories' => ['registry_residence'],
            'needs' => ['admin_procedures' => 10], 'access' => 'in_person', 'booking' => 'required', 'cost' => 'partly_free', 'mediation' => 'not_available',
            'languages' => [],
            'it' => [
                'name' => 'Iscrizione anagrafica e cambio di residenza',
                'summary' => 'Per iscriverti come residente nel Comune o comunicare un nuovo indirizzo.',
                'description' => 'L’anagrafe registra la tua residenza. La residenza serve per la tessera sanitaria, la scuola dei figli, molti aiuti economici.',
                'documents' => "- Passaporto\n- Permesso di soggiorno o ricevuta della domanda\n- Codice fiscale\n- Contratto di affitto o dichiarazione di ospitalità",
                'access_info' => 'Su appuntamento. Dopo la domanda la Polizia locale verifica l’indirizzo.',
                'cost_info' => 'L’iscrizione è gratuita. I certificati possono avere un costo (marca da bollo).',
                'keywords' => 'residenza anagrafe carta identita certificato indirizzo',
            ],
            'en' => ['name' => 'Registering your residence', 'summary' => 'To register as a resident in the municipality or report a new address.'],
            'fr' => ['name' => 'Inscription à l’état civil et changement de résidence', 'summary' => 'Pour vous inscrire comme résident dans la commune ou déclarer une nouvelle adresse.'],
            'ar' => ['name' => 'التسجيل في السجل المدني وتغيير عنوان الإقامة', 'summary' => 'للتسجيل كمقيم في البلدية أو للتصريح بعنوان جديد.'],
            'territories' => ['Piacenza'],
        ],
        [
            'org' => 'ambulatorio', 'sites' => ['sede'], 'category' => 'other_clinics', 'categories' => ['other_clinics', 'health_card'],
            'needs' => ['health' => 10], 'access' => 'in_person', 'booking' => 'not_needed', 'cost' => 'free', 'mediation' => 'available',
            'languages' => [['en', 'staff'], ['fr', 'staff'], ['ar', 'mediator']],
            'it' => [
                'name' => 'Ambulatorio per chi non ha il medico di base',
                'summary' => 'Visite mediche gratuite per chi non ha ancora la tessera sanitaria o il medico di famiglia.',
                'description' => "Medici volontari fanno visite di base e ti aiutano a ottenere la tessera sanitaria o il codice STP.\nIn caso di urgenza grave chiama il 112 o vai al pronto soccorso.",
                'target_audience' => 'Persone senza medico di base, anche senza permesso di soggiorno.',
                'access_info' => 'Senza appuntamento, negli orari di apertura. Arriva presto: si entra in ordine di arrivo.',
                'cost_info' => 'Gratuito.',
                'keywords' => 'medico visita stp tessera sanitaria malattia dottore',
            ],
            'en' => ['name' => 'Clinic for people without a GP', 'summary' => 'Free medical visits for people who do not have a health card or a family doctor yet.'],
            'fr' => ['name' => 'Dispensaire pour les personnes sans médecin traitant', 'summary' => 'Consultations gratuites pour les personnes sans carte sanitaire ni médecin de famille.'],
            'ar' => ['name' => 'عيادة لمن ليس لديه طبيب أسرة', 'summary' => 'فحوصات طبية مجانية لمن لا يملك بعد بطاقة صحية أو طبيب أسرة.'],
        ],
        [
            'org' => 'consultorio', 'sites' => ['piacenza', 'bobbio'], 'category' => 'family_counselling', 'categories' => ['family_counselling', 'ausl'],
            'needs' => ['health' => 8, 'family' => 10], 'access' => 'in_person,phone', 'booking' => 'recommended', 'cost' => 'free', 'mediation' => 'available',
            'languages' => [['en', 'staff'], ['ar', 'mediator'], ['ur', 'mediator']],
            'it' => [
                'name' => 'Consultorio: gravidanza, salute delle donne e dei giovani',
                'summary' => 'Visite in gravidanza, contraccezione e sostegno a donne, coppie e ragazzi.',
                'description' => 'Ostetriche, ginecologhe e psicologhe seguono la gravidanza, offrono visite e consigli sulla contraccezione e sostegno dopo il parto.',
                'target_audience' => 'Donne, coppie e giovani, anche senza tessera sanitaria.',
                'access_info' => 'Meglio telefonare. A Bobbio solo su appuntamento.',
                'cost_info' => 'Gratuito.',
                'keywords' => 'gravidanza incinta ostetrica ginecologa contraccezione donna',
            ],
            'en' => ['name' => 'Family health centre: pregnancy, women’s and young people’s health', 'summary' => 'Pregnancy check-ups, contraception and support for women, couples and young people.'],
            'fr' => ['name' => 'Centre de planning familial : grossesse, santé des femmes et des jeunes', 'summary' => 'Suivi de grossesse, contraception et soutien aux femmes, couples et jeunes.'],
            'ar' => ['name' => 'مركز الاستشارة الأسرية: الحمل وصحة النساء والشباب', 'summary' => 'متابعة الحمل ووسائل منع الحمل ودعم النساء والأزواج والشباب.'],
        ],
        [
            'org' => 'antiviolenza', 'sites' => ['sede'], 'category' => 'protection', 'categories' => ['protection'],
            'needs' => ['family' => 10, 'legal' => 6], 'access' => 'phone,in_person', 'booking' => 'not_needed', 'cost' => 'free', 'mediation' => 'available',
            'languages' => [['en', 'staff'], ['fr', 'staff'], ['es', 'staff'], ['ar', 'mediator']],
            'it' => [
                'name' => 'Ascolto e protezione per donne che subiscono violenza',
                'summary' => 'Se qualcuno ti fa del male, puoi chiamarci. Ti ascoltiamo in modo riservato e gratuito.',
                'description' => "Ascolto telefonico, colloqui, consulenza legale e psicologica, accoglienza in casa rifugio.\nIn pericolo immediato chiama il 112. Numero nazionale antiviolenza: 1522.",
                'access_info' => 'Telefona: l’indirizzo della sede si comunica al telefono per sicurezza.',
                'cost_info' => 'Gratuito e riservato.',
                'keywords' => 'violenza donna maltrattamenti protezione 1522 rifugio',
            ],
            'en' => ['name' => 'Support and protection for women experiencing violence', 'summary' => 'If someone is hurting you, you can call us. We listen confidentially and free of charge.'],
            'fr' => ['name' => 'Écoute et protection pour les femmes victimes de violence', 'summary' => 'Si quelqu’un vous fait du mal, appelez-nous. Écoute confidentielle et gratuite.'],
            'ar' => ['name' => 'الاستماع والحماية للنساء اللواتي يتعرضن للعنف', 'summary' => 'إذا كان أحد يؤذيك يمكنك الاتصال بنا. نستمع إليك بسرية ومجاناً.'],
        ],
        [
            'org' => 'legale', 'sites' => ['sede'], 'category' => 'free_legal_advice', 'categories' => ['free_legal_advice', 'anti_discrimination'],
            'needs' => ['legal' => 10, 'documents' => 5], 'access' => 'in_person,email', 'booking' => 'required', 'cost' => 'free', 'mediation' => 'on_request',
            'languages' => [['en', 'staff'], ['fr', 'staff']],
            'it' => [
                'name' => 'Prima consulenza legale gratuita',
                'summary' => 'Un’avvocata o un avvocato volontario ti ascolta e ti spiega i tuoi diritti.',
                'description' => 'Consulenza su permesso di soggiorno, lavoro, casa, discriminazione. Se serve un avvocato per una causa, ti spieghiamo come chiedere il gratuito patrocinio.',
                'access_info' => 'Scrivi una email o telefona per fissare un appuntamento.',
                'cost_info' => 'La prima consulenza è gratuita.',
                'keywords' => 'avvocato legale diritti discriminazione ricorso gratuito patrocinio',
            ],
            'en' => ['name' => 'Free first legal consultation', 'summary' => 'A volunteer lawyer listens to you and explains your rights.'],
            'fr' => ['name' => 'Première consultation juridique gratuite', 'summary' => 'Un avocat bénévole vous écoute et vous explique vos droits.'],
            'ar' => ['name' => 'استشارة قانونية أولى مجانية', 'summary' => 'يستمع إليك محامٍ متطوع ويشرح لك حقوقك.'],
        ],
        [
            'org' => 'cpia', 'sites' => ['piacenza', 'fiorenzuola'], 'category' => 'italian_courses', 'categories' => ['italian_courses', 'evening_schools'],
            'needs' => ['italian_language' => 10, 'training' => 8], 'access' => 'in_person', 'booking' => 'required', 'cost' => 'free', 'mediation' => 'not_available',
            'languages' => [['en', 'staff'], ['fr', 'staff']],
            'it' => [
                'name' => 'Corsi di italiano e test di livello A2',
                'summary' => 'Corsi gratuiti di italiano per adulti, di giorno e di sera. Test A2 per il permesso di lungo periodo.',
                'description' => 'Corsi dal livello pre-A1 al B1. Corsi per la licenza media. Test di italiano A2 richiesto per il permesso di soggiorno UE di lungo periodo.',
                'target_audience' => 'Adulti e ragazzi dai 16 anni.',
                'documents' => "- Documento d’identità o permesso di soggiorno\n- Codice fiscale",
                'access_info' => 'Iscrizioni in segreteria su appuntamento. Prima c’è un breve test per capire il tuo livello.',
                'cost_info' => 'Gratuito.',
                'keywords' => 'italiano corso scuola a2 licenza media cpia test lingua',
            ],
            'en' => ['name' => 'Italian courses and A2 level test', 'summary' => 'Free Italian courses for adults, day and evening. A2 test for the long-term permit.'],
            'fr' => ['name' => 'Cours d’italien et test de niveau A2', 'summary' => 'Cours d’italien gratuits pour adultes, en journée et le soir. Test A2 pour le titre de longue durée.'],
            'ar' => ['name' => 'دورات اللغة الإيطالية واختبار المستوى A2', 'summary' => 'دورات مجانية في اللغة الإيطالية للكبار نهاراً ومساءً. اختبار A2 لتصريح الإقامة طويل الأمد.'],
        ],
        [
            'org' => 'ponte', 'sites' => ['sede'], 'category' => 'italian_courses', 'categories' => ['italian_courses', 'information_desks'],
            'needs' => ['italian_language' => 7, 'community' => 8], 'access' => 'in_person', 'booking' => 'not_needed', 'cost' => 'free', 'mediation' => 'available',
            'languages' => [['ar', 'staff'], ['fr', 'staff'], ['wo', 'staff']],
            'it' => [
                'name' => 'Corso di italiano serale e sportello di orientamento',
                'summary' => 'Lezioni di italiano con volontari e aiuto a capire a quale ufficio rivolgersi.',
                'description' => 'Due sere a settimana corso di italiano per principianti. Il sabato mattina sportello di orientamento in arabo, francese e wolof.',
                'access_info' => 'Vieni direttamente negli orari di apertura.',
                'cost_info' => 'Gratuito. È gradita l’iscrizione all’associazione.',
                'keywords' => 'italiano volontari orientamento comunita arabo francese wolof',
            ],
            'en' => ['name' => 'Evening Italian course and information desk', 'summary' => 'Italian lessons with volunteers and help to understand which office to go to.'],
            'fr' => ['name' => 'Cours d’italien du soir et guichet d’orientation', 'summary' => 'Cours d’italien avec des bénévoles et aide pour savoir à quel bureau s’adresser.'],
            'ar' => ['name' => 'دورة مسائية في اللغة الإيطالية ومكتب توجيه', 'summary' => 'دروس في اللغة الإيطالية مع متطوعين ومساعدة لمعرفة المكتب الذي يجب التوجه إليه.'],
        ],
        [
            'org' => 'mediazione', 'sites' => ['sede'], 'category' => 'cultural_mediation', 'categories' => ['cultural_mediation'],
            'needs' => ['mediation' => 10], 'access' => 'phone,email', 'booking' => 'required', 'cost' => 'partly_free', 'mediation' => 'available',
            'languages' => [['ar', 'mediator'], ['fr', 'mediator'], ['en', 'mediator'], ['ur', 'mediator'], ['pa', 'mediator'], ['zh', 'mediator'], ['uk', 'mediator'], ['ti', 'mediator']],
            'it' => [
                'name' => 'Mediazione linguistico-culturale su richiesta',
                'summary' => 'Un mediatore o una mediatrice ti accompagna a un appuntamento con un ufficio, un medico o la scuola.',
                'description' => 'La rete organizza la presenza di mediatori in molte lingue durante colloqui con uffici pubblici, servizi sanitari e scuole.',
                'access_info' => 'La richiesta di solito la fa l’ufficio o il servizio. Anche i cittadini possono telefonare per informazioni.',
                'cost_info' => 'Gratuito per i cittadini quando lo richiede un servizio pubblico convenzionato.',
                'keywords' => 'mediatore mediatrice interprete traduttore lingua',
            ],
            'en' => ['name' => 'Language and cultural mediation on request', 'summary' => 'A mediator comes with you to an appointment with an office, a doctor or the school.'],
            'fr' => ['name' => 'Médiation linguistique et culturelle sur demande', 'summary' => 'Un médiateur vous accompagne à un rendez-vous avec un bureau, un médecin ou l’école.'],
            'ar' => ['name' => 'وساطة لغوية وثقافية عند الطلب', 'summary' => 'يرافقك وسيط أو وسيطة إلى موعد في مكتب أو عند الطبيب أو في المدرسة.'],
        ],
        [
            'org' => 'emporio', 'sites' => ['piacenza', 'rottofreno'], 'category' => 'food_clothes', 'categories' => ['food_clothes'],
            'needs' => ['social_support' => 10], 'access' => 'in_person', 'booking' => 'not_needed', 'cost' => 'free', 'mediation' => 'not_available',
            'languages' => [['ar', 'staff'], ['ro', 'staff']],
            'it' => [
                'name' => 'Alimenti e vestiti per famiglie in difficoltà',
                'summary' => 'Spesa gratuita con una tessera a punti e vestiti per adulti e bambini.',
                'description' => 'L’emporio funziona come un piccolo supermercato: con la tessera a punti prendi gratis alimenti e prodotti per la casa.',
                'requirements' => 'La tessera si ottiene su invio dei servizi sociali o di un centro di ascolto.',
                'access_info' => 'Porta la tessera negli orari di apertura.',
                'cost_info' => 'Gratuito.',
                'keywords' => 'cibo spesa alimenti vestiti pacco famiglie',
            ],
            'en' => ['name' => 'Food and clothes for families in need', 'summary' => 'Free groceries with a points card, and clothes for adults and children.'],
            'fr' => ['name' => 'Nourriture et vêtements pour les familles en difficulté', 'summary' => 'Courses gratuites avec une carte à points et vêtements pour adultes et enfants.'],
            'ar' => ['name' => 'مواد غذائية وملابس للأسر المحتاجة', 'summary' => 'تسوّق مجاني ببطاقة نقاط وملابس للكبار والأطفال.'],
        ],
        [
            'org' => 'comune', 'sites' => ['anagrafe'], 'category' => 'preschool_enrolment', 'categories' => ['preschool_enrolment', 'study_services'],
            'needs' => ['school_children' => 10], 'access' => 'in_person,online', 'booking' => 'required', 'cost' => 'partly_free', 'mediation' => 'on_request',
            'languages' => [], 'online_url' => 'https://example.org/comune/iscrizioni',
            'it' => [
                'name' => 'Iscrizione a nido, scuola dell’infanzia, mensa e trasporto',
                'summary' => 'Informazioni e aiuto per iscrivere i figli al nido, alla scuola dell’infanzia e ai servizi scolastici.',
                'description' => 'Tutti i bambini hanno diritto alla scuola, anche senza permesso di soggiorno. L’ufficio aiuta con le iscrizioni e le tariffe di mensa e trasporto.',
                'documents' => "- Documento dei genitori\n- Codice fiscale del bambino\n- ISEE per le riduzioni",
                'access_info' => 'Le iscrizioni si fanno online nei periodi indicati; allo sportello ti aiutano a compilare.',
                'cost_info' => 'Nido e mensa hanno tariffe in base all’ISEE.',
                'keywords' => 'scuola iscrizione nido asilo mensa trasporto figli bambini',
            ],
            'en' => ['name' => 'Enrolment in nursery, kindergarten, school meals and transport', 'summary' => 'Information and help to enrol your children in nursery, kindergarten and school services.'],
            'fr' => ['name' => 'Inscription à la crèche, à la maternelle, à la cantine et au transport', 'summary' => 'Informations et aide pour inscrire vos enfants à la crèche, à la maternelle et aux services scolaires.'],
            'ar' => ['name' => 'التسجيل في الحضانة وروضة الأطفال والمطعم والنقل المدرسي', 'summary' => 'معلومات ومساعدة لتسجيل أطفالك في الحضانة وروضة الأطفال والخدمات المدرسية.'],
        ],
        [
            'org' => 'sospeso', 'sites' => ['sede'], 'category' => 'community_associations', 'categories' => ['community_associations', 'after_school'],
            'needs' => ['community' => 8], 'access' => 'in_person', 'booking' => 'not_needed', 'cost' => 'free', 'mediation' => 'not_available',
            'languages' => [['uk', 'staff'], ['ru', 'staff']],
            'it' => [
                'name' => 'Incontri della comunità e doposcuola',
                'summary' => 'Il sabato pomeriggio incontri, attività culturali e aiuto compiti per bambini.',
                'description' => 'Servizio dimostrativo di un’organizzazione con accesso sospeso alla piattaforma: resta visibile al pubblico (punto aperto Q-17).',
                'access_info' => 'Vieni il sabato pomeriggio.',
                'cost_info' => 'Gratuito.',
                'keywords' => 'comunita incontri doposcuola compiti cultura',
            ],
            'en' => ['name' => 'Community meetings and after-school', 'summary' => 'On Saturday afternoon: meetings, cultural activities and homework help for children.'],
            'fr' => ['name' => 'Rencontres de la communauté et aide aux devoirs', 'summary' => 'Le samedi après-midi : rencontres, activités culturelles et aide aux devoirs.'],
            'ar' => ['name' => 'لقاءات الجالية والدعم المدرسي', 'summary' => 'يوم السبت بعد الظهر: لقاءات وأنشطة ثقافية ومساعدة الأطفال في الواجبات.'],
        ],
    ];

    $db = $c->get(Database::class);
    $db->transaction(static function (Database $db) use ($organizations, $services): void {
        $territory = static fn (string $name): int => (int) ($db->fetchValue("SELECT id FROM territories WHERE type = 'municipality' AND name = ?", [$name])
            ?? throw new RuntimeException("Comune sconosciuto: $name"));
        $category = static fn (string $code): int => (int) ($db->fetchValue('SELECT id FROM categories WHERE code = ?', [$code])
            ?? throw new RuntimeException("Categoria sconosciuta: $code"));
        $now = gmdate('Y-m-d H:i:s');

        $orgIds = [];
        $siteIds = [];
        foreach ($organizations as $key => $org) {
            $orgId = $db->fetchValue('SELECT id FROM organizations WHERE name = ?', [$org['name']]);
            $values = [
                'is_community_based' => $org['community'],
                'website' => $org['website'],
                'census_status' => 'censused',
                'listing_status' => 'listed',
                'verification_status' => 'verified',
                'publication_status' => 'published',
                'published_at' => $now,
                'verified_at' => $now,
            ];
            if ($orgId === null) {
                $typeId = (int) $db->fetchValue('SELECT id FROM organization_types WHERE code = ?', [$org['type']]);
                $orgId = $db->insert('organizations', [
                    'name' => $org['name'],
                    'organization_type_id' => $typeId,
                    'access_status' => 'not_enabled',
                    'status_note' => 'Dato dimostrativo fittizio',
                    ...$values,
                ]);
            } else {
                $orgId = (int) $orgId;
                $db->update('organizations', $values, ['id' => $orgId]);
            }
            $orgIds[$key] = $orgId;

            if ($db->fetchValue('SELECT COUNT(*) FROM sites WHERE organization_id = ?', [$orgId]) > 0) {
                // Già caricata: recupera le sedi per nome.
                foreach ($org['sites'] as $siteKey => $site) {
                    $siteIds[$key][$siteKey] = (int) $db->fetchValue('SELECT id FROM sites WHERE organization_id = ? AND name = ?', [$orgId, $site[0]]);
                }
                continue;
            }

            $db->execute(
                "INSERT IGNORE INTO organization_translations (organization_id, locale, slug, description, status) VALUES (?, 'it', ?, ?, 'approved')",
                [$orgId, Slug::make($org['name']), $org['description']],
            );
            foreach ($org['languages'] as $code) {
                $db->execute('INSERT IGNORE INTO organization_languages (organization_id, language_code) VALUES (?, ?)', [$orgId, $code]);
            }
            if ($org['website'] !== null) {
                $db->insert('contact_points', ['owner_type' => 'organization', 'owner_id' => $orgId, 'kind' => 'website', 'value' => $org['website'], 'visibility' => 'public']);
            }
            // Referente fittizio: solo per amministratori (privacy by default)
            $db->insert('contact_points', ['owner_type' => 'organization', 'owner_id' => $orgId, 'kind' => 'email', 'value' => 'referente.' . $key . '@example.org', 'label_key' => 'contact.label.referent', 'visibility' => 'admin']);

            foreach ($org['sites'] as $siteKey => [$name, $address, $cap, $town, $lat, $lng, $stepFree, $hours, $phone, $email, $directions]) {
                $siteId = $db->insert('sites', [
                    'organization_id' => $orgId,
                    'name' => $name,
                    'address_line' => $address,
                    'postal_code' => $cap,
                    'territory_id' => $territory($town),
                    'lat' => $lat,
                    'lng' => $lng,
                    'geo_source' => $lat === null ? null : 'manual',
                    'geo_checked' => $lat === null ? 0 : 1,
                    'is_public_place' => empty($org['hidden_site']) ? 1 : 0,
                    'step_free_access' => $stepFree,
                    'publication_status' => 'published',
                    'published_at' => $now,
                    'verified_at' => $now,
                ]);
                $siteIds[$key][$siteKey] = $siteId;
                if ($directions !== null) {
                    $db->insert('site_translations', ['site_id' => $siteId, 'locale' => 'it', 'directions' => $directions, 'status' => 'approved']);
                }
                foreach ($hours as [$from, $to, $opens, $closes, $appointment]) {
                    for ($day = $from; $day <= $to; $day++) {
                        $db->insert('opening_hours', [
                            'owner_type' => 'site', 'owner_id' => $siteId, 'weekday' => $day,
                            'opens_at' => $opens, 'closes_at' => $closes, 'by_appointment' => $appointment,
                        ]);
                    }
                }
                $db->insert('contact_points', ['owner_type' => 'site', 'owner_id' => $siteId, 'kind' => 'phone', 'value' => $phone, 'visibility' => 'public', 'sort_order' => 1]);
                if ($email !== null) {
                    $db->insert('contact_points', ['owner_type' => 'site', 'owner_id' => $siteId, 'kind' => 'email', 'value' => $email, 'visibility' => 'public', 'sort_order' => 2]);
                }
            }
        }

        foreach ($services as $service) {
            $orgId = $orgIds[$service['org']];
            $existing = $db->fetchValue(
                "SELECT s.id FROM services s JOIN service_translations t ON t.service_id = s.id AND t.locale = 'it'
                  WHERE s.organization_id = ? AND t.name = ?",
                [$orgId, $service['it']['name']],
            );
            if ($existing !== null) {
                continue;
            }

            $serviceId = $db->insert('services', [
                'organization_id' => $orgId,
                'primary_category_id' => $category($service['category']),
                'access_modes' => $service['access'],
                'booking' => $service['booking'],
                'cost_type' => $service['cost'],
                'mediation' => $service['mediation'],
                'online_url' => $service['online_url'] ?? null,
                'publication_status' => 'published',
                'published_at' => $now,
                'verified_at' => $now,
                'next_review_at' => gmdate('Y-m-d', strtotime('+180 days')),
            ]);

            foreach (['it', 'en', 'fr', 'ar'] as $locale) {
                $texts = $service[$locale];
                $db->insert('service_translations', [
                    'service_id' => $serviceId,
                    'locale' => $locale,
                    'slug' => Slug::make($texts['name']),
                    'name' => $texts['name'],
                    'summary' => $texts['summary'] ?? null,
                    'description' => $texts['description'] ?? null,
                    'target_audience' => $texts['target_audience'] ?? null,
                    'requirements' => $texts['requirements'] ?? null,
                    'documents' => $texts['documents'] ?? null,
                    'access_info' => $texts['access_info'] ?? null,
                    'booking_info' => $texts['booking_info'] ?? null,
                    'cost_info' => $texts['cost_info'] ?? null,
                    'keywords' => $texts['keywords'] ?? null,
                    'status' => 'approved',
                    'source_hash' => hash('sha256', $service['it']['name']),
                ]);
            }
            foreach (array_unique([$service['category'], ...$service['categories']]) as $code) {
                $db->insert('service_categories', ['service_id' => $serviceId, 'category_id' => $category($code)]);
            }
            foreach ($service['needs'] as $needCode => $relevance) {
                $db->execute('INSERT INTO service_needs (service_id, need_id, relevance) SELECT ?, id, ? FROM needs WHERE code = ?', [$serviceId, $relevance, $needCode]);
            }
            foreach ($service['sites'] as $index => $siteKey) {
                $db->insert('service_sites', ['service_id' => $serviceId, 'site_id' => $siteIds[$service['org']][$siteKey], 'is_main' => $index === 0 ? 1 : 0]);
            }
            foreach ($service['languages'] as [$code, $mode]) {
                $db->insert('service_languages', ['service_id' => $serviceId, 'language_code' => $code, 'mode' => $mode]);
            }
            foreach ($service['territories'] ?? [] as $town) {
                $db->insert('service_territories', ['service_id' => $serviceId, 'territory_id' => $territory($town)]);
            }
        }
    });
};
