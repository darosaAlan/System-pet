<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireAuth();

$db = new Database();
$message = '';
$messageType = '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Buscar espécies para o select
$stmt = $db->prepare("SELECT * FROM especies ORDER BY especie");
$stmt->execute();
$especies = $stmt->fetchAll();

// Processar formulário
if ($_POST) {
    $nome = trim($_POST['nome'] ?? '');
    $nascimento = $_POST['nascimento'] ?? '';
    $especie_id = $_POST['especie_id'] ?? '';
    $prontuario = trim($_POST['prontuario'] ?? '');
    $genero = $_POST['genero'] ?? '';
    
    $errors = [];
    
    if (empty($nome)) $errors[] = 'O nome do pet é obrigatório.';
    if (empty($nascimento)) $errors[] = 'A data de nascimento é obrigatória.';
    if (empty($especie_id)) $errors[] = 'A espécie é obrigatória.';
    if (empty($genero)) $errors[] = 'O gênero é obrigatório.';
    
    // Validar data
    if ($nascimento && !DateTime::createFromFormat('Y-m-d', $nascimento)) {
        $errors[] = 'Data de nascimento inválida.';
    }
    
    if (count($errors) > 0) {
        $message = implode('<br>', $errors);
        $messageType = 'danger';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $db->prepare("INSERT INTO pets (nome, nascimento, especie_id, prontuario, genero) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $nascimento, $especie_id, $prontuario, $genero]);
                $message = 'Pet cadastrado com sucesso!';
                $messageType = 'success';
                $action = 'list';
            } else if ($action === 'edit' && $id) {
                $stmt = $db->prepare("UPDATE pets SET nome = ?, nascimento = ?, especie_id = ?, prontuario = ?, genero = ? WHERE id = ?");
                $stmt->execute([$nome, $nascimento, $especie_id, $prontuario, $genero, $id]);
                $message = 'Pet atualizado com sucesso!';
                $messageType = 'success';
                $action = 'list';
            }
        } catch (PDOException $e) {
            $message = 'Erro ao salvar pet: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Processar exclusão
if ($action === 'delete' && $id) {
    try {
        $stmt = $db->prepare("DELETE FROM pets WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Pet excluído com sucesso!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Erro ao excluir pet: ' . $e->getMessage();
        $messageType = 'danger';
    }
    $action = 'list';
}

// Buscar dados para edição
$pet = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->execute([$id]);
    $pet = $stmt->fetch();
    
    if (!$pet) {
        $message = 'Pet não encontrado.';
        $messageType = 'danger';
        $action = 'list';
    }
}

// Listar pets
if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $whereClause = '';
    $params = [];
    
    if ($search) {
        $whereClause = 'WHERE p.nome LIKE ? OR e.especie LIKE ?';
        $params = ["%$search%", "%$search%"];
    }
    
    $stmt = $db->prepare("
        SELECT p.*, e.especie 
        FROM pets p 
        JOIN especies e ON p.especie_id = e.id 
        $whereClause
        ORDER BY p.nome
    ");
    $stmt->execute($params);
    $pets = $stmt->fetchAll();
}

$title = 'Gerenciar Pets - Sistema de Pets';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="fas fa-dog me-2"></i>
        <?php if ($action === 'create'): ?>
            Novo Pet
        <?php elseif ($action === 'edit'): ?>
            Editar Pet
        <?php else: ?>
            Pets
        <?php endif; ?>
    </h1>
    
    <?php if ($action === 'list'): ?>
        <a href="pets.php?action=create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Novo Pet
        </a>
    <?php else: ?>
        <a href="pets.php" class="btn btn-secondary">
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
    <?php if (count($especies) == 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            É necessário cadastrar pelo menos uma espécie antes de cadastrar pets.
            <a href="especies.php?action=create" class="btn btn-sm btn-warning ms-2">
                Cadastrar Espécie
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <?php echo $action === 'create' ? 'Cadastrar Novo Pet' : 'Editar Pet'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome" class="form-label">Nome do Pet *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                           value="<?php echo htmlspecialchars($pet['nome'] ?? $_POST['nome'] ?? ''); ?>" 
                                           required maxlength="100">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="nascimento" class="form-label">Data de Nascimento *</label>
                                    <input type="date" class="form-control" id="nascimento" name="nascimento" 
                                           value="<?php echo $pet['nascimento'] ?? $_POST['nascimento'] ?? ''; ?>" 
                                           required max="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="especie_id" class="form-label">Espécie *</label>
                                    <select class="form-select" id="especie_id" name="especie_id" required>
                                        <option value="">Selecione a espécie</option>
                                        <?php foreach ($especies as $especie): ?>
                                            <option value="<?php echo $especie['id']; ?>" 
                                                    <?php echo ($pet['especie_id'] ?? $_POST['especie_id'] ?? '') == $especie['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($especie['especie']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gênero *</label>
                                    <div class="mt-2">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="genero" id="macho" value="macho" 
                                                   <?php echo ($pet['genero'] ?? $_POST['genero'] ?? '') === 'macho' ? 'checked' : ''; ?> required>
                                            <label class="form-check-label" for="macho">
                                                <i class="fas fa-mars text-primary me-1"></i>Macho
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="genero" id="femea" value="femea" 
                                                   <?php echo ($pet['genero'] ?? $_POST['genero'] ?? '') === 'femea' ? 'checked' : ''; ?> required>
                                            <label class="form-check-label" for="femea">
                                                <i class="fas fa-venus text-danger me-1"></i>Fêmea
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="prontuario" class="form-label">Prontuário</label>
                                <textarea class="form-control" id="prontuario" name="prontuario" rows="4" 
                                          placeholder="Informações sobre o pet, histórico médico, observações..."><?php echo htmlspecialchars($pet['prontuario'] ?? $_POST['prontuario'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?php echo $action === 'create' ? 'Cadastrar Pet' : 'Atualizar Pet'; ?>
                                </button>
                                <a href="pets.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Busca -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Buscar por nome do pet ou espécie..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        <?php if ($_GET['search'] ?? ''): ?>
                            <a href="pets.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (count($pets) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Espécie</th>
                                <th>Gênero</th>
                                <th>Nascimento</th>
                                <th>Idade</th>
                                <th width="120">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pets as $p): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-paw me-2 text-primary"></i>
                                    <strong><?php echo htmlspecialchars($p['nome']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($p['especie']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $p['genero'] == 'macho' ? 'primary' : 'danger'; ?>">
                                        <i class="fas fa-<?php echo $p['genero'] == 'macho' ? 'mars' : 'venus'; ?> me-1"></i>
                                        <?php echo ucfirst($p['genero']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($p['nascimento'])); ?></td>
                                <td>
                                    <?php
                                    $idade = date_diff(date_create($p['nascimento']), date_create('today'));
                                    if ($idade->y > 0) {
                                        echo $idade->y . ' ano' . ($idade->y > 1 ? 's' : '');
                                    } else if ($idade->m > 0) {
                                        echo $idade->m . ' mês' . ($idade->m > 1 ? 'es' : '');
                                    } else {
                                        echo $idade->d . ' dia' . ($idade->d > 1 ? 's' : '');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm btn-group-actions">
                                        <button type="button" class="btn btn-outline-info" title="Ver Detalhes"
                                                data-bs-toggle="modal" data-bs-target="#petModal<?php echo $p['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="pets.php?action=edit&id=<?php echo $p['id']; ?>" 
                                           class="btn btn-outline-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="pets.php?action=delete&id=<?php echo $p['id']; ?>" 
                                           class="btn btn-outline-danger" title="Excluir"
                                           onclick="return confirmarExclusao('<?php echo htmlspecialchars($p['nome']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-paw fa-3x mb-3"></i>
                    <h5>
                        <?php if ($_GET['search'] ?? ''): ?>
                            Nenhum pet encontrado
                        <?php else: ?>
                            Nenhum pet cadastrado
                        <?php endif; ?>
                    </h5>
                    <p>
                        <?php if ($_GET['search'] ?? ''): ?>
                            Tente uma busca diferente ou cadastre um novo pet.
                        <?php else: ?>
                            Comece cadastrando o primeiro pet do sistema.
                        <?php endif; ?>
                    </p>
                    <a href="pets.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Cadastrar Pet
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modals para detalhes dos pets -->
    <?php foreach ($pets as $p): ?>
    <div class="modal fade" id="petModal<?php echo $p['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-paw me-2"></i><?php echo htmlspecialchars($p['nome']); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6"><strong>Espécie:</strong></div>
                        <div class="col-6"><?php echo htmlspecialchars($p['especie']); ?></div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6"><strong>Gênero:</strong></div>
                        <div class="col-6">
                            <span class="badge bg-<?php echo $p['genero'] == 'macho' ? 'primary' : 'danger'; ?>">
                                <?php echo ucfirst($p['genero']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6"><strong>Nascimento:</strong></div>
                        <div class="col-6"><?php echo date('d/m/Y', strtotime($p['nascimento'])); ?></div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6"><strong>Cadastrado em:</strong></div>
                        <div class="col-6"><?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></div>
                    </div>
                    
                    <?php if ($p['prontuario']): ?>
                        <hr>
                        <h6><strong>Prontuário:</strong></h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($p['prontuario'])); ?></p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <a href="pets.php?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>