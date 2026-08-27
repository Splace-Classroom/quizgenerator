<?php
$formurl = new moodle_url('/mod/quizgenerator/generate.php', ['id' => $id]);
?>
<h3>Generate Quiz</h3>
<form action="<?= $formurl ?>" method="post" class="mform">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="course_id" value="<?= $course->id ?>">

    <div class="fitem">
        <label class="fitemtitle">Course</label>
        <div class="felement">
            <div class="form-control-static"><?= format_string($course->fullname) ?> (ID: <?= $course->id ?>)</div>
        </div>
    </div>

    <div class="fitem">
        <label for="quizquery" class="fitemtitle">Quiz Query</label>
        <div class="felement">
            <input type="text" id="quizquery" name="quizquery" class="form-control" placeholder="Type keywords...">
        </div>
    </div>

    <div class="fitem">
        <label for="module_id" class="fitemtitle"><?= get_string('selectmodule', 'quizgenerator') ?></label>
        <div class="felement">
            <select id="module_id" name="module_id" class="custom-select" required>
                <option value=""><?= get_string('choosemodule', 'quizgenerator') ?></option>
                <?php if (isset($course_modules) && !empty($course_modules)): ?>
                    <?php foreach ($course_modules as $module): ?>
                        <option value="<?= $module['id'] ?>">
                            Section <?= $module['section'] ?>: <?= htmlspecialchars($module['name']) ?> (<?= ucfirst($module['modname']) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small class="form-text text-muted">
                <?= get_string('moduleselectionhelp', 'quizgenerator') ?>
                <?php if (!isset($course_modules) || empty($course_modules)): ?>
                    <br><em><?= get_string('nomodulesfound', 'quizgenerator') ?></em>
                <?php else: ?>
                    <br>Found <?= count($course_modules) ?> module(s) in this course.
                <?php endif; ?>
            </small>
        </div>
    </div>

    <div class="fitem">
        <label class="fitemtitle">Question Type & Number</label>
        <div class="felement">
            <div style="margin-bottom: 5px;">
                <label><input type="checkbox" name="qtypes[]" value="Multiple Choice with One Answer" checked> Multiple Choice with One Answer</label>
                <input type="number" name="qnums[Multiple Choice with One Answer]" class="form-control" style="width: 80px; display: inline-block; margin-left: 10px;" min="1" max="20" value="1">
            </div>
            <div style="margin-bottom: 5px;">
                <label><input type="checkbox" name="qtypes[]" value="Essay"> Essay</label>
                <input type="number" name="qnums[Essay]" class="form-control" style="width: 80px; display: inline-block; margin-left: 10px;" min="1" max="20" value="1">
            </div>
            <div style="margin-bottom: 5px;">
                <label><input type="checkbox" name="qtypes[]" value="Multiple Choice with Multiple Answers"> Multiple Choice with Multiple Answers</label>
                <input type="number" name="qnums[Multiple Choice with Multiple Answers]" class="form-control" style="width: 80px; display: inline-block; margin-left: 10px;" min="1" max="20" value="1">
            </div>
        </div>
    </div>

    <div class="fitem">
        <div class="felement">
            <input type="submit" name="generate" value="Generate Questions" class="btn btn-primary">
        </div>
    </div>
</form>

<style>
    .fitem {
        margin-bottom: 1rem;
    }
</style>