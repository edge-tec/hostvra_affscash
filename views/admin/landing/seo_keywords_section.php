<?php
/**
 * SEO Keywords Section Component (Post Editor Collapsible Module)
 *
 * Integrates into post editor pages seamlessly.
 * Supports: Primary Keyword, Secondary Keywords, Long-tail Keywords, Related Keywords, Focus Keyword.
 * Includes tag badges, auto-complete, copy-paste comma splitting, AJAX save, and auto-generated SEO preview.
 */

$currentPostId = isset($postId) ? (int)$postId : (isset($post['id']) ? (int)$post['id'] : 0);
$existingKeywords = SeoKeywordModule::getKeywords($currentPostId);
$csrfToken = Auth::generateCsrf();
?>

<!-- SEO Keywords Collapsible Section -->
<div class="card mb-3" id="seo-keywords-module" style="border: 1px solid #CBD5E1; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <div class="card-header" id="seo-keywords-header" style="background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%); padding: 14px 18px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; user-select: none;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="background: #4F46E5; color: #fff; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800;">🔑</div>
            <div>
                <h3 style="font-size: 15px; font-weight: 800; color: #0F172A; margin: 0;">SEO Keywords</h3>
                <div style="font-size: 12px; color: #64748B; margin-top: 2px;">Tag-based keyword strategy, autocomplete, and auto-generated search metadata</div>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="badge" style="background: #EEF2FF; color: #4F46E5; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px;">SEO &amp; AI Search Ready</span>
            <span id="seo-keywords-toggle-icon" style="font-size: 16px; color: #64748B; transition: transform .25s ease;">▼</span>
        </div>
    </div>

    <div class="card-body" id="seo-keywords-body" style="padding: 20px; display: block;">

        <style>
            .sk-field-group {
                margin-bottom: 20px;
                background: #FFFFFF;
                border: 1px solid #E2E8F0;
                border-radius: 8px;
                padding: 16px;
                transition: border-color .2s;
            }
            .sk-field-group:focus-within {
                border-color: #818CF8;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            }
            .sk-label-box {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 8px;
            }
            .sk-label {
                font-size: 13.5px;
                font-weight: 700;
                color: #1E293B;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .sk-hint {
                font-size: 11.5px;
                color: #64748B;
            }
            .sk-tag-container {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                align-items: center;
                background: #F8FAFC;
                border: 1px solid #CBD5E1;
                border-radius: 8px;
                padding: 8px 10px;
                min-height: 46px;
            }
            .sk-tag {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #4F46E5;
                color: #FFFFFF;
                font-size: 12px;
                font-weight: 600;
                padding: 4px 10px;
                border-radius: 20px;
                box-shadow: 0 2px 4px rgba(79,70,229,0.15);
                animation: fadeInTag .2s ease-out;
            }
            .sk-tag.tag-primary   { background: linear-gradient(135deg, #4F46E5, #4338CA); }
            .sk-tag.tag-secondary { background: linear-gradient(135deg, #0284C7, #0369A1); }
            .sk-tag.tag-long_tail { background: linear-gradient(135deg, #0D9488, #0F766E); }
            .sk-tag.tag-related   { background: linear-gradient(135deg, #7C3AED, #6D28D9); }
            .sk-tag.tag-focus     { background: linear-gradient(135deg, #D97706, #B45309); }

            .sk-tag .sk-remove {
                cursor: pointer;
                opacity: 0.8;
                font-size: 14px;
                font-weight: 800;
                line-height: 1;
                margin-left: 2px;
                transition: opacity .15s;
            }
            .sk-tag .sk-remove:hover { opacity: 1; color: #FECACA; }

            .sk-input-wrapper {
                flex: 1;
                min-width: 180px;
                position: relative;
            }
            .sk-input {
                width: 100%;
                border: none;
                outline: none;
                background: transparent;
                font-size: 13px;
                color: #0F172A;
                padding: 4px 6px;
            }
            .sk-input::placeholder { color: #94A3B8; }

            .sk-suggestions {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #FFFFFF;
                border: 1px solid #CBD5E1;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.12);
                z-index: 100;
                max-height: 180px;
                overflow-y: auto;
                display: none;
                margin-top: 4px;
            }
            .sk-suggestion-item {
                padding: 8px 12px;
                font-size: 12.5px;
                color: #334155;
                cursor: pointer;
                transition: background .15s;
            }
            .sk-suggestion-item:hover {
                background: #EEF2FF;
                color: #4F46E5;
                font-weight: 600;
            }
            @keyframes fadeInTag {
                from { opacity: 0; transform: scale(0.9); }
                to   { opacity: 1; transform: scale(1); }
            }
        </style>

        <div class="sk-fields-wrapper">
            <?php
            $fieldConfigs = [
                'primary' => [
                    'label' => 'Primary Keyword',
                    'icon'  => '🎯',
                    'badge' => 'tag-primary',
                    'hint'  => 'Main target keyword for this article (e.g. Affiliate Network)',
                    'placeholder' => 'Type primary keyword and press Enter or comma...'
                ],
                'secondary' => [
                    'label' => 'Secondary Keywords',
                    'icon'  => '📌',
                    'badge' => 'tag-secondary',
                    'hint'  => 'Supporting keywords & variations (e.g. CPA Network, Dating Offers, CPL Offers)',
                    'placeholder' => 'Type or paste multiple separated by commas...'
                ],
                'long_tail' => [
                    'label' => 'Long-tail Keywords',
                    'icon'  => '🔍',
                    'badge' => 'tag-long_tail',
                    'hint'  => 'Specific multi-word search queries (e.g. best cpa affiliate network for beginners)',
                    'placeholder' => 'Type long-tail phrase and press Enter...'
                ],
                'related' => [
                    'label' => 'Related Keywords',
                    'icon'  => '🔗',
                    'badge' => 'tag-related',
                    'hint'  => 'Contextual LSI terms and topic associations (e.g. affiliate payout rates, smartlink)',
                    'placeholder' => 'Type related topic keyword...'
                ],
                'focus' => [
                    'label' => 'Focus Keyword',
                    'icon'  => '⭐',
                    'badge' => 'tag-focus',
                    'hint'  => 'Core focus term for density & AI overview targeting',
                    'placeholder' => 'Type main focus keyword...'
                ]
            ];

            foreach ($fieldConfigs as $typeKey => $cfg):
                $tags = $existingKeywords[$typeKey] ?? [];
            ?>
            <div class="sk-field-group" data-type="<?= $typeKey ?>">
                <div class="sk-label-box">
                    <div class="sk-label">
                        <span><?= $cfg['icon'] ?></span>
                        <span><?= $cfg['label'] ?></span>
                    </div>
                    <div class="sk-hint"><?= $cfg['hint'] ?></div>
                </div>

                <div class="sk-tag-container" id="sk-container-<?= $typeKey ?>" onclick="document.getElementById('sk-input-<?= $typeKey ?>').focus()">
                    <!-- Hidden inputs container for standard form submit -->
                    <div class="sk-hidden-inputs" id="sk-hidden-<?= $typeKey ?>">
                        <?php foreach ($tags as $t): ?>
                        <input type="hidden" name="seo_keywords[<?= $typeKey ?>][]" value="<?= Helpers::e($t) ?>">
                        <?php endforeach; ?>
                    </div>

                    <!-- Render existing tags -->
                    <?php foreach ($tags as $t): ?>
                    <span class="sk-tag <?= $cfg['badge'] ?>" data-value="<?= Helpers::e($t) ?>">
                        <span><?= Helpers::e($t) ?></span>
                        <span class="sk-remove" onclick="removeSkTag(this, '<?= $typeKey ?>', '<?= Helpers::e(addslashes($t)) ?>')">&times;</span>
                    </span>
                    <?php endforeach; ?>

                    <div class="sk-input-wrapper">
                        <input type="text" class="sk-input" id="sk-input-<?= $typeKey ?>"
                               placeholder="<?= empty($tags) ? $cfg['placeholder'] : 'Add another...' ?>"
                               autocomplete="off"
                               onkeydown="handleSkKeydown(event, '<?= $typeKey ?>', '<?= $cfg['badge'] ?>')"
                               onpaste="handleSkPaste(event, '<?= $typeKey ?>', '<?= $cfg['badge'] ?>')"
                               oninput="handleSkInput(this, '<?= $typeKey ?>')">
                        <div class="sk-suggestions" id="sk-suggestions-<?= $typeKey ?>"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Action bar -->
        <div style="margin-top: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding-top: 16px; border-top: 1px solid #E2E8F0;">
            <div style="display: flex; gap: 10px;">
                <?php if ($currentPostId > 0): ?>
                <button type="button" class="btn btn-primary btn-sm" id="btn-save-seo-keywords" onclick="saveSeoKeywordsAjax()" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
                    💾 Save SEO Keywords (AJAX)
                </button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary btn-sm" onclick="generateSeoPreview()" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                    ⚡ Preview Auto-SEO Metadata
                </button>
            </div>
            <div id="sk-ajax-msg" style="font-size: 13px; font-weight: 700; color: #10B981; display: none;"></div>
        </div>

        <!-- Auto-Generated SEO Preview Drawer -->
        <div id="sk-seo-preview-box" style="display: none; margin-top: 20px; background: #F8FAFC; border: 1px solid #C7D2FE; border-radius: 10px; padding: 18px;">
            <h4 style="font-size: 14px; font-weight: 800; color: #3730A3; margin-top: 0; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                ✨ Auto-Generated Search &amp; AI Metadata Suggestions
            </h4>
            <p style="font-size: 12px; color: #4338CA; margin-bottom: 14px;">
                The system uses your entered keywords to automatically compute standard SEO metadata, Open Graph, Twitter Cards, and JSON-LD Schema. <em>Note: If you have already filled in SEO fields, custom values are never overwritten.</em>
            </p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div style="background: #fff; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px;">
                    <div style="font-size: 11px; font-weight: 700; color: #64748B;">META TITLE SUGGESTION</div>
                    <div id="prev-meta-title" style="font-size: 13px; font-weight: 700; color: #0F172A; margin-top: 4px;">—</div>
                </div>
                <div style="background: #fff; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px;">
                    <div style="font-size: 11px; font-weight: 700; color: #64748B;">META DESCRIPTION SUGGESTION</div>
                    <div id="prev-meta-desc" style="font-size: 12.5px; color: #334155; margin-top: 4px;">—</div>
                </div>
            </div>

            <div style="margin-top: 12px; background: #fff; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px;">
                <div style="font-size: 11px; font-weight: 700; color: #64748B; margin-bottom: 6px;">JSON-LD SCHEMA GRAPH (BlogPosting)</div>
                <pre id="prev-json-ld" style="background: #0F172A; color: #38BDF8; font-size: 11.5px; padding: 10px; border-radius: 6px; max-height: 160px; overflow-y: auto; margin: 0;"></pre>
            </div>
        </div>

    </div>
</div>

<script>
// Collapsible Accordion Toggle
document.getElementById('seo-keywords-header').addEventListener('click', function() {
    var body = document.getElementById('seo-keywords-body');
    var icon = document.getElementById('seo-keywords-toggle-icon');
    if (body.style.display === 'none') {
        body.style.display = 'block';
        icon.style.transform = 'rotate(0deg)';
    } else {
        body.style.display = 'none';
        icon.style.transform = 'rotate(-90deg)';
    }
});

// Remove Tag Function
function removeSkTag(el, type, val) {
    var tagEl = el.closest('.sk-tag');
    if (tagEl) tagEl.remove();

    // Remove matching hidden input
    var container = document.getElementById('sk-hidden-' + type);
    if (container) {
        var hiddenInputs = container.querySelectorAll('input');
        hiddenInputs.forEach(function(inp) {
            if (inp.value.toLowerCase() === val.toLowerCase()) {
                inp.remove();
            }
        });
    }
}

// Add Tag Helper
function addSkTag(type, val, badgeClass) {
    val = val.trim();
    if (!val) return;

    var container = document.getElementById('sk-container-' + type);
    var hiddenBox = document.getElementById('sk-hidden-' + type);
    var inputWrap = container.querySelector('.sk-input-wrapper');

    // Check duplicate
    var existingTags = container.querySelectorAll('.sk-tag');
    for (var i = 0; i < existingTags.length; i++) {
        if (existingTags[i].getAttribute('data-value').toLowerCase() === val.toLowerCase()) {
            return; // Skip duplicate
        }
    }

    // Create tag pill element
    var tag = document.createElement('span');
    tag.className = 'sk-tag ' + badgeClass;
    tag.setAttribute('data-value', val);
    tag.innerHTML = '<span>' + escapeHtml(val) + '</span><span class="sk-remove" onclick="removeSkTag(this, \'' + type + '\', \'' + escapeJsStr(val) + '\')">&times;</span>';

    // Create hidden input
    var hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'seo_keywords[' + type + '][]';
    hidden.value = val;
    hiddenBox.appendChild(hidden);

    // Insert before input wrapper
    container.insertBefore(tag, inputWrap);
}

// Keydown Handler
function handleSkKeydown(e, type, badgeClass) {
    var input = e.target;
    var val = input.value.trim();

    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        if (val) {
            addSkTag(type, val, badgeClass);
            input.value = '';
            hideSuggestions(type);
        }
    } else if (e.key === 'Backspace' && !val) {
        // Delete last tag on backspace if input empty
        var container = document.getElementById('sk-container-' + type);
        var tags = container.querySelectorAll('.sk-tag');
        if (tags.length > 0) {
            var lastTag = tags[tags.length - 1];
            var tagVal = lastTag.getAttribute('data-value');
            removeSkTag(lastTag.querySelector('.sk-remove'), type, tagVal);
        }
    }
}

// Paste Handler (auto splits comma-separated strings)
function handleSkPaste(e, type, badgeClass) {
    e.preventDefault();
    var clipboardData = (e.clipboardData || window.clipboardData).getData('text');
    if (!clipboardData) return;

    var items = clipboardData.split(/[\r\n,]+/);
    items.forEach(function(item) {
        var clean = item.trim();
        if (clean) {
            addSkTag(type, clean, badgeClass);
        }
    });
    e.target.value = '';
    hideSuggestions(type);
}

// Autocomplete Input Handler
var debounceTimers = {};
function handleSkInput(input, type) {
    var val = input.value.trim();
    if (debounceTimers[type]) clearTimeout(debounceTimers[type]);

    if (val.length < 1) {
        hideSuggestions(type);
        return;
    }

    debounceTimers[type] = setTimeout(function() {
        fetch('/admin/seo-keywords/ajax?action=autocomplete&q=' + encodeURIComponent(val))
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success && res.suggestions && res.suggestions.length > 0) {
                showSuggestions(type, res.suggestions, input);
            } else {
                hideSuggestions(type);
            }
        })
        .catch(function() { hideSuggestions(type); });
    }, 200);
}

function showSuggestions(type, list, input) {
    var box = document.getElementById('sk-suggestions-' + type);
    if (!box) return;
    box.innerHTML = '';
    list.forEach(function(item) {
        var div = document.createElement('div');
        div.className = 'sk-suggestion-item';
        div.textContent = item;
        div.onclick = function() {
            var fieldGroup = input.closest('.sk-field-group');
            var badgeClass = 'tag-' + type;
            addSkTag(type, item, badgeClass);
            input.value = '';
            hideSuggestions(type);
            input.focus();
        };
        box.appendChild(div);
    });
    box.style.display = 'block';
}

function hideSuggestions(type) {
    var box = document.getElementById('sk-suggestions-' + type);
    if (box) box.style.display = 'none';
}

// Hide autocomplete on click outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.sk-input-wrapper')) {
        document.querySelectorAll('.sk-suggestions').forEach(function(el) {
            el.style.display = 'none';
        });
    }
});

// AJAX Save Keywords
function saveSeoKeywordsAjax() {
    var postId = <?= $currentPostId ?>;
    if (postId <= 0) {
        alert('Please publish or save the article first before saving keywords via AJAX.');
        return;
    }

    var btn = document.getElementById('btn-save-seo-keywords');
    var msg = document.getElementById('sk-ajax-msg');
    if (btn) btn.disabled = true;
    if (msg) { msg.style.display = 'block'; msg.style.color = '#4F46E5'; msg.textContent = 'Saving keywords...'; }

    var formData = new FormData();
    formData.append('action', 'save_keywords');
    formData.append('post_id', postId);
    formData.append('_token', '<?= $csrfToken ?>');

    // Collect tags by type
    var types = ['primary', 'secondary', 'long_tail', 'related', 'focus'];
    types.forEach(function(t) {
        var container = document.getElementById('sk-container-' + t);
        if (container) {
            var tags = container.querySelectorAll('.sk-tag');
            tags.forEach(function(tag) {
                formData.append('keywords[' + t + '][]', tag.getAttribute('data-value'));
            });
        }
    });

    fetch('/admin/seo-keywords/ajax', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (btn) btn.disabled = false;
        if (data.success) {
            if (msg) {
                msg.style.color = '#10B981';
                msg.textContent = '✓ Saved ' + data.savedCount + ' keyword(s) successfully!';
                setTimeout(function() { msg.style.display = 'none'; }, 4000);
            }
        } else {
            if (msg) {
                msg.style.color = '#EF4444';
                msg.textContent = '⚠ ' + (data.error || 'Failed to save keywords');
            }
        }
    })
    .catch(function(err) {
        if (btn) btn.disabled = false;
        if (msg) {
            msg.style.color = '#EF4444';
            msg.textContent = '⚠ Network error saving keywords';
        }
    });
}

// Generate Realtime SEO Preview
function generateSeoPreview() {
    var postId = <?= $currentPostId ?>;
    var previewBox = document.getElementById('sk-seo-preview-box');

    var titleInput = document.querySelector('input[name="title"]');
    var excerptInput = document.querySelector('textarea[name="excerpt"]');

    var formData = new FormData();
    formData.append('action', 'preview_seo');
    formData.append('post_id', postId);
    formData.append('_token', '<?= $csrfToken ?>');
    if (titleInput) formData.append('title', titleInput.value);
    if (excerptInput) formData.append('excerpt', excerptInput.value);

    var types = ['primary', 'secondary', 'long_tail', 'related', 'focus'];
    types.forEach(function(t) {
        var container = document.getElementById('sk-container-' + t);
        if (container) {
            var tags = container.querySelectorAll('.sk-tag');
            tags.forEach(function(tag) {
                formData.append('keywords[' + t + '][]', tag.getAttribute('data-value'));
            });
        }
    });

    fetch('/admin/seo-keywords/ajax', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success && data.preview) {
            document.getElementById('prev-meta-title').textContent = data.preview.meta_title;
            document.getElementById('prev-meta-desc').textContent = data.preview.meta_description;
            document.getElementById('prev-json-ld').textContent = JSON.stringify(data.preview.json_ld, null, 2);
            previewBox.style.display = 'block';
        }
    })
    .catch(function() {});
}

function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
function escapeJsStr(str) {
    return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}
</script>
