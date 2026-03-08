<?php
/**
 * Public business profile share form.
 * Rendered standalone — no WP admin chrome.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$token     = sanitize_text_field( wp_unslash( $_GET['contenthub_share'] ) );
$rest_url  = esc_url( rest_url( 'contenthub-wp/v1/public/business-profile' ) );
$site_name = esc_html( get_bloginfo( 'name' ) );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Business Profile — <?php echo $site_name; ?></title>
    <style>
        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f0f2f5;
            color: #1d2327;
            line-height: 1.5;
            min-height: 100vh;
        }

        /* ── Layout ── */
        .chub-share-page {
            max-width: 680px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        /* ── Header ── */
        .chub-share-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .chub-share-badge {
            display: inline-block;
            background: #1d2327;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 16px;
        }

        .chub-share-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1d2327;
            margin-bottom: 8px;
        }

        .chub-share-header p {
            font-size: 15px;
            color: #646970;
            max-width: 480px;
            margin: 0 auto;
        }

        /* ── Progress Steps ── */
        .chub-steps {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 32px;
        }

        .chub-step-dot {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: #a7aaad;
            background: #fff;
            border: 1px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .chub-step-dot:hover { border-color: #c3c4c7; }

        .chub-step-dot.is-active {
            background: #1d2327;
            border-color: #1d2327;
            color: #fff;
        }

        .chub-step-dot.is-completed {
            background: #fff;
            border-color: #00a32a;
            color: #00a32a;
        }

        .chub-step-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 700;
            background: #f0f0f0;
            color: #50575e;
        }

        .chub-step-dot.is-active .chub-step-num {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }

        .chub-step-dot.is-completed .chub-step-num {
            background: #00a32a;
            color: #fff;
        }

        /* ── Card ── */
        .chub-card {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }

        .chub-card-header {
            padding: 16px 24px;
            border-bottom: 1px solid #f0f0f0;
        }

        .chub-card-header h2 {
            font-size: 16px;
            font-weight: 600;
            color: #1d2327;
        }

        .chub-card-body {
            padding: 24px;
        }

        /* ── Form Fields ── */
        .chub-field {
            margin-bottom: 20px;
        }

        .chub-field:last-child {
            margin-bottom: 0;
        }

        .chub-field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1d2327;
            margin-bottom: 6px;
        }

        .chub-field label .chub-required {
            color: #d63638;
            margin-left: 2px;
        }

        .chub-field input[type="text"],
        .chub-field textarea,
        .chub-field select {
            width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            line-height: 1.5;
            color: #1d2327;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            font-family: inherit;
        }

        .chub-field input:focus,
        .chub-field textarea:focus,
        .chub-field select:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }

        .chub-field textarea { resize: vertical; min-height: 80px; }
        .chub-field select { cursor: pointer; }

        .chub-field-row {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        .chub-field-row .chub-field { flex: 1; margin-bottom: 0; }

        .chub-na-btn {
            padding: 8px 14px;
            font-size: 13px;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            background: #f6f7f7;
            color: #50575e;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .chub-na-btn:hover { background: #f0f0f0; border-color: #8c8f94; }
        .chub-na-btn.is-active { background: #1d2327; border-color: #1d2327; color: #fff; }

        /* ── Navigation ── */
        .chub-nav {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
        }

        .chub-btn {
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .chub-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .chub-btn-secondary {
            background: #f6f7f7;
            color: #1d2327;
            border: 1px solid #c3c4c7;
        }

        .chub-btn-secondary:hover:not(:disabled) { background: #f0f0f0; }

        .chub-btn-primary {
            background: #1d2327;
            color: #fff;
        }

        .chub-btn-primary:hover:not(:disabled) { background: #2c3338; }

        /* ── Notices ── */
        .chub-notice {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .chub-notice-success {
            background: #edfaef;
            border: 1px solid #00a32a;
            color: #00450a;
        }

        .chub-notice-error {
            background: #fcf0f1;
            border: 1px solid #d63638;
            color: #8a1114;
        }

        /* ── Footer ── */
        .chub-share-footer {
            text-align: center;
            margin-top: 32px;
            font-size: 12px;
            color: #a7aaad;
        }

        /* ── Loading ── */
        .chub-loading {
            text-align: center;
            padding: 60px 20px;
            color: #646970;
            font-size: 15px;
        }

        .chub-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #e0e0e0;
            border-top-color: #1d2327;
            border-radius: 50%;
            animation: chub-spin 0.6s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        @keyframes chub-spin { to { transform: rotate(360deg); } }

        /* ── Hidden sections ── */
        .chub-section { display: none; }
        .chub-section.is-active { display: block; }

        /* ── Responsive ── */
        @media (max-width: 600px) {
            .chub-share-page { padding: 20px 16px 40px; }
            .chub-steps { flex-wrap: wrap; justify-content: center; }
            .chub-step-dot span:not(.chub-step-num) { display: none; }
            .chub-card-body { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="chub-share-page">
        <div class="chub-share-header">
            <div class="chub-share-badge">OBX ContentHub</div>
            <h1>Business Profile</h1>
            <p>Help us understand your business so we can create content that sounds like you. Fill out what you can — every bit helps.</p>
        </div>

        <div id="chub-app">
            <div class="chub-loading">
                <span class="chub-spinner"></span> Loading form...
            </div>
        </div>

        <div class="chub-share-footer">
            Powered by OBX ContentHub &middot; OuterBox
        </div>
    </div>

    <script>
    (function() {
        const REST_URL = <?php echo wp_json_encode( $rest_url ); ?>;
        const TOKEN    = <?php echo wp_json_encode( $token ); ?>;
        const app      = document.getElementById('chub-app');

        let profile = {};
        let schema  = {};
        let sections = [];
        let activeStep = 0;
        let saving = false;

        async function apiFetch(method, body) {
            const opts = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Share-Token': TOKEN,
                },
            };
            if (body) opts.body = JSON.stringify(body);
            const res = await fetch(REST_URL, opts);
            return res.json();
        }

        function render() {
            if (!sections.length) {
                app.innerHTML = '<div class="chub-loading"><span class="chub-spinner"></span> Loading form...</div>';
                return;
            }

            const [sectionKey, sectionData] = sections[activeStep];
            let html = '';

            // Steps
            html += '<div class="chub-steps">';
            sections.forEach(([key, sec], i) => {
                const cls = i === activeStep ? 'is-active' : (i < activeStep ? 'is-completed' : '');
                const check = i < activeStep ? '&#10003;' : (i + 1);
                html += `<button class="chub-step-dot ${cls}" onclick="window.__chubStep(${i})">
                    <span class="chub-step-num">${check}</span>
                    <span>${esc(sec.label)}</span>
                </button>`;
            });
            html += '</div>';

            // Card
            html += '<div class="chub-card">';
            html += `<div class="chub-card-header"><h2>${esc(sectionData.label)}</h2></div>`;
            html += '<div class="chub-card-body">';

            Object.entries(sectionData.fields).forEach(([fieldKey, config]) => {
                const value = profile[fieldKey] || '';

                if (config.type === 'select') {
                    html += `<div class="chub-field">
                        <label>${esc(config.label)}</label>
                        <select onchange="window.__chubUpdate('${fieldKey}', this.value)">
                            <option value="">— Select —</option>
                            ${config.options.map(opt =>
                                `<option value="${esc(opt)}" ${value === opt ? 'selected' : ''}>${esc(opt)}</option>`
                            ).join('')}
                        </select>
                    </div>`;
                } else if (config.type === 'textarea') {
                    html += `<div class="chub-field">
                        <label>${esc(config.label)}</label>
                        <textarea rows="3" onchange="window.__chubUpdate('${fieldKey}', this.value)"
                            oninput="window.__chubUpdate('${fieldKey}', this.value)">${esc(value)}</textarea>
                    </div>`;
                } else {
                    if (config.allow_na) {
                        const isNA = value === 'N/A';
                        html += `<div class="chub-field-row">
                            <div class="chub-field">
                                <label>${esc(config.label)}</label>
                                <input type="text" value="${esc(value)}"
                                    onchange="window.__chubUpdate('${fieldKey}', this.value)"
                                    oninput="window.__chubUpdate('${fieldKey}', this.value)">
                            </div>
                            <button class="chub-na-btn ${isNA ? 'is-active' : ''}"
                                onclick="window.__chubToggleNA('${fieldKey}')">${isNA ? 'Clear' : 'N/A'}</button>
                        </div>`;
                    } else {
                        const req = config.required ? '<span class="chub-required">*</span>' : '';
                        html += `<div class="chub-field">
                            <label>${esc(config.label)}${req}</label>
                            <input type="text" value="${esc(value)}"
                                onchange="window.__chubUpdate('${fieldKey}', this.value)"
                                oninput="window.__chubUpdate('${fieldKey}', this.value)">
                        </div>`;
                    }
                }
            });

            html += '</div></div>';

            // Navigation
            html += '<div class="chub-nav">';
            html += `<button class="chub-btn chub-btn-secondary" ${activeStep === 0 ? 'disabled' : ''}
                onclick="window.__chubStep(${activeStep - 1})">Previous</button>`;
            if (activeStep < sections.length - 1) {
                html += `<button class="chub-btn chub-btn-primary"
                    onclick="window.__chubStep(${activeStep + 1})">Next</button>`;
            } else {
                html += `<button class="chub-btn chub-btn-primary" ${saving ? 'disabled' : ''}
                    onclick="window.__chubSave()">${saving ? 'Saving...' : 'Submit Profile'}</button>`;
            }
            html += '</div>';

            app.innerHTML = html;
        }

        function esc(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

        window.__chubStep = function(i) { activeStep = i; render(); };
        window.__chubUpdate = function(key, val) { profile[key] = val; };

        window.__chubToggleNA = function(key) {
            profile[key] = profile[key] === 'N/A' ? '' : 'N/A';
            render();
        };

        window.__chubSave = async function() {
            if (!profile.company_name || !profile.company_name.trim()) {
                app.innerHTML = '<div class="chub-notice chub-notice-error">Company Name is required before submitting.</div>';
                setTimeout(render, 3000);
                return;
            }
            saving = true;
            render();
            try {
                await apiFetch('POST', profile);
                app.innerHTML = '<div class="chub-notice chub-notice-success">Business profile submitted successfully. Thank you!</div>';
            } catch (err) {
                saving = false;
                const errHtml = '<div class="chub-notice chub-notice-error">Something went wrong. Please try again.</div>';
                app.innerHTML = errHtml;
                setTimeout(render, 3000);
            }
        };

        // Init
        apiFetch('GET').then(res => {
            profile = res.data || {};
            schema  = res.schema || {};
            sections = Object.entries(schema);
            render();
        }).catch(() => {
            app.innerHTML = '<div class="chub-notice chub-notice-error">Could not load form. The link may have expired.</div>';
        });
    })();
    </script>
</body>
</html>
