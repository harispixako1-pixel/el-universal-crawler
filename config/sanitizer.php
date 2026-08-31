<?php

return [
    'default_language' => 'es',

    'rules' => [
        'es' => [
            'junk_phrases' => [
                // Advertisements & Social Media Prompts
                '/^\s*\[?\s*publicidad\s*\]?\s*$/i',
                '/^\s*(?:lee|véase|mira|consulta)\s+(?:también|mas|más)\b.*$/i',
                '/^\s*suscríbet?e\s+a\s+(?:nuestro\s+boletín|nuestra\s+newsletter).*$/i',
                '/^\s*(?:derechos\s+reservados|copyright)\b.*$/i',
                '/^\s*síguenos\s+en\s+.*$/i',
                '/^\s*compartir\s+en\s+.*$/i',
                '/^\s*(?:Únete|Unete)\s+a\s+nuestro\s+canal.*$/i',
                '/^.*¡?EL\s+UNIVERSAL\s+ya\s+está\s+en\s+Whatsapp!?.*/i',

                // Contact Details, Handles & External Links
                '/^\s*[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\s*$/i', // Emails
                '/^\s*@[a-zA-Z0-9_]+\s*$/i',                                 // Handles (@user)
                '/^\s*(?:http:\/\/|https:\/\/)?(?:www\.)?[a-zA-Z0-9-]+\.[a-zA-Z]{2,}(?:\/[^\s]*)?\s*$/i', // Domains

                // Bylines & Short Identifiers
                '/^[a-z]{2,4}$/i',
                '/^\s*(?:Instantáneas|Stent)\s*:\s*$/i',
            ],
            'source_attributions' => [
                '/^\s*(?:Fuente|Fuentes|Vía|Cortesía\s+de)\s*:\s*.+$/i',
                '/^\s*Lee\s+más\s+en\s*:\s*.+$/i',
                '/^\s*Foto\s*:\s*.+$/i',
            ],
        ],

        'en' => [
            'junk_phrases' => [
                '/^\s*\[?\s*advertisement\s*\]?\s*$/i',
                '/^\s*(?:read|see|check)\s+(?:also|more)\b.*$/i',
                '/^\s*subscribe\s+to\s+our\s+newsletter.*$/i',
                '/^\s*join\s+our\s+(?:whatsapp|telegram)\s+channel.*$/i',
                '/^\s*all\s+rights\s+reserved.*$/i',
                '/^\s*follow\s+us\s+on\s+.*$/i',
                '/^\s*share\s+on\s+.*$/i',
                '/^\s*[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\s*$/i',
                '/^\s*@[a-zA-Z0-9_]+\s*$/i',
                '/^\s*(?:http:\/\/|https:\/\/)?(?:www\.)?[a-zA-Z0-9-]+\.[a-zA-Z]{2,}(?:\/[^\s]*)?\s*$/i',
                '/^[a-z]{2,4}$/i',
            ],
            'source_attributions' => [
                '/^\s*(?:Source|Sources|Via|Courtesy\s+of)\s*:\s*.+$/i',
                '/^\s*Read\s+more\s+(?:at|on)\s*:\s*.+$/i',
                '/^\s*Photo\s*:\s*.+$/i',
            ],
        ],

        'pt' => [
            'junk_phrases' => [
                '/^\s*\[?\s*publicidade\s*\]?\s*$/i',
                '/^\s*(?:leia|veja|confira)\s+(?:também|tambem|mais)\b.*$/i',
                '/^\s*inscreva-se\s+na\s+nossa\s+newsletter.*$/i',
                '/^\s*junte-se\s+ao\s+nosso\s+canal.*$/i',
                '/^\s*todos\s+os\s+direitos\s+reservados.*$/i',
                '/^\s*siga-nos\s+nas?\s+.*$/i',
                '/^\s*compartilhar\s+no\s+.*$/i',
                '/^\s*[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\s*$/i',
                '/^\s*@[a-zA-Z0-9_]+\s*$/i',
                '/^\s*(?:http:\/\/|https:\/\/)?(?:www\.)?[a-zA-Z0-9-]+\.[a-zA-Z]{2,}(?:\/[^\s]*)?\s*$/i',
                '/^[a-z]{2,4}$/i',
            ],
            'source_attributions' => [
                '/^\s*(?:Fonte|Fontes|Via|Cortesia\s+de)\s*:\s*.+$/i',
                '/^\s*Leia\s+mais\s+em\s*:\s*.+$/i',
                '/^\s*Foto\s*:\s*.+$/i',
            ],
        ],
    ],

    'generic_patterns' => [
        '/^\[?\s*https?:\/\/[^\s\]]+\s*\]?$/i',
        '/^\[?\s*www\.[^\s\]]+\s*\]?$/i',
    ],
];