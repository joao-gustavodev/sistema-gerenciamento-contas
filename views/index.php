<?php include __DIR__ . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Painel de Chamados</h2>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>#ID</th>
                <th>Solicitante</th>
                <th>Título</th>
                <th>Prioridade</th>
                <th>Status</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="7" class="text-center">Nenhum chamado encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td>#<?= sprintf('%04d', $ticket['id']); ?></td>
                        <td><?= htmlspecialchars($ticket['requester']); ?></td>
                        <td>
                            <strong><?= htmlspecialchars($ticket['title']); ?></strong>
                            <p class="desc-preview"><?= htmlspecialchars($ticket['description']); ?></p>
                        </td>
                        <td>
                            <span class="badge badge-priority-<?= strtolower($ticket['priority']); ?>">
                                <?= $ticket['priority']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-status-<?= strtolower(str_replace(' ', '-', $ticket['status'])); ?>">
                                <?= $ticket['status']; ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></td>
                        <td>
                            <form action="index.php?action=update-status" method="POST" class="inline-form">
                                <input type="hidden" name="id" value="<?= $ticket['id']; ?>">
                                <select name="status" onchange="this.form.submit()" class="select-sm">
                                    <option value="Aberto" <?= $ticket['status'] === 'Aberto' ? 'selected' : ''; ?>>Aberto</option>
                                    <option value="Em Andamento" <?= $ticket['status'] === 'Em Andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                                    <option value="Fechado" <?= $ticket['status'] === 'Fechado' ? 'selected' : ''; ?>>Fechado</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>