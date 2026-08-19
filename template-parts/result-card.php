<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="result-card <?= $args["classes"] ?>">
    <div class="result-card__wrapper">
        <div class="result-card__inner tx-center">

            <span class="result-card__amount">$<?= format_number_abbreviated($args["numerical_amount"]) ?></span>

            <?php print_title($args["case_title"], $args["title_tag"], "result-card__title"); ?>

            <?php if (isset($args["case_description"]) && $args["case_description"]): ?>
                <p class="result-card__description"><?= $args["case_description"] ?></p>
            <?php endif ?>
        </div>
    </div>
</div>