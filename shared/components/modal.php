<?php
/**
 * Modal Component
 * Componente reutilizável para modais
 *
 * Uso:
 * $modalConfig = [
 *     'id' => 'myModal',
 *     'title' => 'Título do Modal',
 *     'size' => 'md', // sm, md, lg
 * ];
 * renderModal($modalConfig, function() {
 *     // Conteúdo do modal aqui
 * });
 */

function renderModal($config, $contentCallback) {
    $id = $config['id'] ?? 'modal';
    $title = $config['title'] ?? 'Modal';
    $size = $config['size'] ?? 'md';

    $widths = [
        'sm' => '400px',
        'md' => '600px',
        'lg' => '800px',
        'xl' => '1000px',
    ];

    $width = $widths[$size] ?? $widths['md'];
    ?>
    <div id="<?= $id ?>" class="modal-overlay">
        <div class="modal" style="width: <?= $width ?>; max-width: 90%;">
            <div class="modal-header">
                <h3 class="modal-title"><?= $title ?></h3>
                <button type="button" class="modal-close" onclick="closeModal('<?= $id ?>')">&times;</button>
            </div>
            <div class="modal-body">
                <?php $contentCallback(); ?>
            </div>
        </div>
    </div>
    <?php
}
?>
