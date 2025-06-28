<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireAuth();

$db = new Database();

// Buscar estatísticas
$stmt = $db->prepare("SELECT COUNT(*) as total FROM pets");
$stmt->execute();
$totalPets = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM especies");
$stmt->execute();
$totalEspecies = $stmt->fetch()['total'];

// Últimos pets cadastrados
$stmt = $db->prepare("
    SELECT p.nome, p.nascimento, e.especie, p.genero 
    FROM pets p 
    JOIN especies e ON p.especie_id = e.id 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$ultimosPets = $stmt->fetchAll();

$title = 'Dashboard - Sistema de Pets';
include 'includes/header.php';
?>

<h1 class="mb-4">
    <i class="fas fa-home me-2"></i>Dashboard
    <small class="text-muted">Bem-vindo, <?php echo getUserName(); ?>!</small>
</h1>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total de Pets</h5>
                        <h2 class="mb-0"><?php echo $totalPets; ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-dog fa-3x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-primary border-0">
                <a href="pets.php" class="text-white text-decoration-none">
                    Ver todos <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Espécies Cadastradas</h5>
                        <h2 class="mb-0"><?php echo $totalEspecies; ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-list fa-3x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-success border-0">
                <a href="especies.php" class="text-white text-decoration-none">
                    Gerenciar <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>Últimos Pets Cadastrados
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($ultimosPets) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Espécie</th>
                                    <th>Gênero</th>
                                    <th>Nascimento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimosPets as $pet): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-paw me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($pet['nome']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($pet['especie']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $pet['genero'] == 'macho' ? 'primary' : 'pink'; ?>">
                                            <?php echo ucfirst($pet['genero']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($pet['nascimento'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-paw fa-3x mb-3"></i>
                        <p>Nenhum pet cadastrado ainda.</p>
                        <a href="pets.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Cadastrar Primeiro Pet
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-rocket me-2"></i>Ações Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="pets.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Novo Pet
                    </a>
                    <a href="especies.php?action=create" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Nova Espécie
                    </a>
                    <a href="pets.php" class="btn btn-outline-primary">
                        <i class="fas fa-search me-2"></i>Buscar Pets
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Sobre o Sistema
                </h5>
            </div>
            <div class="card-body">
                <p class="card-text">
                    Sistema completo para gerenciamento de pets com funcionalidades de 
                    cadastro, edição e controle de espécies.
                </p>
                <small class="text-muted">
                    Desenvolvido em PHP com MySQL
                </small>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>