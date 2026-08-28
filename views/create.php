<?php include __DIR__ . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Abrir Novo Chamado</h2>
    <a href="index.php" class="btn btn-secondary">Voltar</a>
</div>

<div class="form-card">
    <form action="index.php?action=create" method="POST">
        <div class="form-group">
            <label for="requester">Seu Nome / Solicitante</label>
            <input type="text" id="requester" name="requester" required placeholder="Ex: Maria Silva">
        </div>

        <div class="form-row">
            <div class="form-group flex-2">
                <label for="title">Título do Chamado</label>
                <input type="text" id="title" name="title" required placeholder="Ex: Impressora não responde">
            </div>
            
            <div class="form-group flex-1">
                <label for="priority">Prioridade</label>
                <select id="priority" name="priority">
                    <option value="Baixa">Baixa</option>
                    <option value="Média" selected>Média</option>
                    <option value="Alta">Alta</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Descrição Detalhada</label>
            <textarea id="description" name="description" rows="5" required placeholder="Descreva o problema com o máximo de detalhes possível..."></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Registrar Chamado</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>