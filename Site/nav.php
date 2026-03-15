<header>
    <div class="header-container">
        <div class="logo">
            <a href="/Agri/Future-Agriculteur/Site/index.php">
                <i class="fas fa-seedling"></i> <strong>Planning Agricole</strong> 
            </a>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="/Agri/Future-Agriculteur/Site/index.php">Accueil</a></li>
                <li><a href="/Agri/Future-Agriculteur/Site/planificateur.php">Planificateur</a></li>
                <li><a href="/Agri/Future-Agriculteur/Site/algo.php">Algorithmes utilisés</a></li>
            </ul>
        </nav>

        <div class="header-auth">
            <?php if (isset($_SESSION['agriculteur'])): ?>
                <a href="/Agri/Future-Agriculteur/Site/connexion/mon_compte.php" class="btn-account">
    <i class="fas fa-user-circle"></i> Mon Compte
</a>
                <a href="/Agri/Future-Agriculteur/Site/connexion/deconnexion.php" class="btn-login">Déconnexion</a>
            <?php else: ?>
                <a href="/Agri/Future-Agriculteur/Site/connexion/connexion.php" class="btn-login">Connexion</a>
            <?php endif; ?>
        </div>
    </div>
</header>