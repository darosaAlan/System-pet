<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireAuth();

$db = new Database();
$message = '';
$messageType = '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Processar formulário
if ($_POST) {
    $especie = trim($_POST['especie'] ?? '');
    
    if (empty($especie)) {
        $message = 'O nome da espécie é obrigatório.';
        $messageType = 'danger';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $db->prepare("INSERT INTO especies (especie) VALUES (?)");
                $stmt->execute([$especie]);
                $message = 'Espécie cadastrada com sucesso!';
                $messageType = 'success';
                $action = 'list';
            } else if ($action === 'edit' && $id) {
                $stmt = $db->prepare("UPDATE especies SET especie = ? WHERE id = ?");
                $stmt->execute([$especie, $id]);
                $message = 'Espécie atualizada com sucesso!';
                $messageType = 'success';
                $action = 'list';
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = 'Esta espécie já está cadastrada.';
            } else {
                $message = 'Erro ao salvar espécie: ' . $e->getMessage();
            }
            $messageType = 'danger';
        }
    }
}

// Processar exclusão
if ($action === 'delete' && $id) {
    try {
        // Verificar se há pets desta espécie
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM pets WHERE especie_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetch()['total'];
        
        if ($count > 0) {
            $message = 'Não é possível excluir esta espécie pois existem pets cadastrados com ela.';
            $messageType = 'warning';
        } else {
            $stmt = $db->prepare("DELETE FROM especies WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Espécie excluída com sucesso!';
            $messageType = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Erro ao excluir espécie: ' . $e->getMessage();
        $messageType = 'danger';
    }
    $action = 'list';
}

// Buscar dados para edição
$especie = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM especies WHERE id = ?");
    $stmt->execute([$id]);
    $especie = $stmt->fetch();
    
    if (!$especie) {
        $message = 'Espécie não encontrada.';
        $messageType = 'danger';
        $action = 'list';
    }
}

// Listar espécies
if ($action === 'list') {
    $stmt = $db->prepare("
        SELECT e.*, 
               (SELECT COUNT(*) FROM pets WHERE especie_id = e.id) as total_pets
        FROM especies e 
        ORDER BY e.especie
    ");
    $stmt->execute();
    $especies = $stmt->fetchAll();
}

$title = 'Gerenciar Espécies - Sistema de Pets';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="fas fa-list me-2"></i>
        <?php if ($action === 'create'): ?>
            Nova Espécie
        <?php elseif ($action === 'edit'): ?>
            Editar Espécie
        <?php else: ?>
            Espécies
        <?php endif; ?>
    </h1>
    
    <?php if ($action === 'list'): ?>
        <a href="especies.php?action=create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nova Espécie
        </a>
    <?php else: ?>
        <a href="especies.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Voltar
        </a>
    <?php endif; ?>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo $action === 'create' ? 'Cadastrar Nova Espécie' : 'Editar Espécie'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="especie" class="form-label">Nome da Espécie *</label>
                            <input type="text" class="form-control" id="especie" name="especie" 
                                   value="<?php echo htmlspecialchars($especie['especie'] ?? $_POST['especie'] ?? ''); ?>" 
                                   required maxlength="50">
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?php echo $action === 'create' ? 'Cadastrar' : 'Atualizar'; ?>
                            </button>
                            <a href="especies.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="card">
        <div class="card-body">
            <?php if (count($especies) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Espécie</th>
                                <th>Pets Cadastrados</th>
                                <th>Data de Cadastro</th>
                                <th width="120">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($especies as $esp): ?>
                            <tr>
                                <td><?php echo $esp['id']; ?></td>
                                <td>
                                    <i class="fas fa-tag me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($esp['especie']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $esp['total_pets']; ?> pets</span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($esp['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm btn-group-actions">
                                        <a href="especies.php?action=edit&id=<?php echo $esp['id']; ?>" 
                                           class="btn btn-outline-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($esp['total_pets'] == 0): ?>
                                        <a href="especies.php?action=delete&id=<?php echo $esp['id']; ?>" 
                                           class="btn btn-outline-danger" title="Excluir"
                                           onclick="return confirmarExclusao('<?php echo htmlspecialchars($esp['especie']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-outline-secondary" disabled title="Não é possível excluir pois há pets cadastrados">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-list fa-3x mb-3"></i>
                    <h5>Nenhuma espécie cadastrada</h5>
                    <p>Comece cadastrando uma nova espécie para os pets.</p>
                    <a href="especies.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Cadastrar Primeira Espécie
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>