<?php
Auth::check('admin');
$pageTitle = 'Registration Questions';

// ── One-time dedup migration ──────────────────────────────────────────────
// 1. Delete duplicate rows keeping the one with the lowest id per (question_text, target_role)
try {
    Database::query(
        "DELETE rq1 FROM registration_questions rq1
         INNER JOIN registration_questions rq2
             ON  rq1.question_text = rq2.question_text
             AND rq1.target_role   = rq2.target_role
             AND rq1.id > rq2.id"
    );
} catch (Exception $e) {}
// 2. Add unique constraint so future inserts cannot create duplicates
try {
    Database::query(
        "ALTER TABLE registration_questions
         ADD UNIQUE KEY uq_rq_text_role (question_text(100), target_role)"
    );
} catch (Exception $e) {}
// ─────────────────────────────────────────────────────────────────────────

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $action = Helpers::post('action');

    if ($action === 'add') {
        $opts = null;
        if (in_array(Helpers::post('field_type'), ['select','radio','checkbox'])) {
            $rawOpts = array_filter(array_map('trim', explode("\n", Helpers::postRaw('options'))));
            $opts = json_encode(array_values($rawOpts));
        }
        $questionText = Helpers::postRaw('question_text');
        $targetRole   = Helpers::post('target_role');
        // Duplicate-safe insert: skip silently if same question+role already exists
        $existing = Database::fetchOne(
            "SELECT id FROM registration_questions WHERE question_text = ? AND target_role = ?",
            [$questionText, $targetRole]
        );
        if ($existing) {
            Helpers::flash('warning', 'A question with this text already exists for the selected role.');
        } else {
            Database::insert('registration_questions', [
                'target_role'   => $targetRole,
                'question_text' => $questionText,
                'field_type'    => Helpers::post('field_type'),
                'options'       => $opts,
                'is_required'   => isset($_POST['is_required']) ? 1 : 0,
                'sort_order'    => (int)($_POST['sort_order'] ?? 0),
                'is_active'     => 1,
            ]);
            Helpers::flash('success', 'Question added.');
        }
    }

    if ($action === 'toggle') {
        $id = (int)Helpers::postRaw('id');
        $q  = Database::fetchOne("SELECT is_active FROM registration_questions WHERE id=?", [$id]);
        if ($q) Database::update('registration_questions', ['is_active' => $q['is_active'] ? 0 : 1], 'id=?', [$id]);
        Helpers::flash('success', 'Question toggled.');
    }

    if ($action === 'delete') {
        Database::delete('registration_questions', 'id=?', [(int)Helpers::postRaw('id')]);
        Helpers::flash('success', 'Question deleted.');
    }

    Helpers::redirect('/admin/registration-questions');
}

$questions = Database::fetchAll("SELECT * FROM registration_questions ORDER BY sort_order, id");

require BASE_PATH . '/views/admin/registration_questions/index.php';
