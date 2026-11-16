<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require '../config/db.php';

$page = 'dashboard';
$page_title = 'Dashboard';


$totalCategories = (int) $pdo->query(
    "SELECT COUNT(*) FROM categories"
)->fetchColumn();

$totalItems = (int) $pdo->query(
    "SELECT COUNT(*) FROM items"
)->fetchColumn();

$inactiveItems = (int) $pdo->query(
    "SELECT COUNT(*) FROM items WHERE is_active = 0"
)->fetchColumn();

$visibleItems = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM items i
     JOIN categories c ON i.category_id = c.id
     WHERE i.is_active = 1 AND c.is_active = 1"
)->fetchColumn();

// Menu Health
$missingPhotos = (int) $pdo->query(
    "SELECT COUNT(*) FROM items
     WHERE image IS NULL OR TRIM(image) = ''"
)->fetchColumn();

$missingDescriptions = (int) $pdo->query(
    "SELECT COUNT(*) FROM items
     WHERE description IS NULL OR TRIM(description) = ''"
)->fetchColumn();

$emptyCategories = (int) $pdo->query(
    "SELECT COUNT(*) FROM categories c
     WHERE NOT EXISTS (
       SELECT 1 FROM items i WHERE i.category_id = c.id
     )"
)->fetchColumn();

$hiddenByCategory = (int) $pdo->query(
    "SELECT COUNT(*) FROM items i
     JOIN categories c ON i.category_id = c.id
     WHERE i.is_active = 1 AND c.is_active = 0"
)->fetchColumn();

$attentionChecks = [
    [
        'number' => '01',
        'title' => 'Missing product photos',
        'detail' => 'Items without an uploaded image',
        'count' => $missingPhotos,
        'link' => 'items.php'
    ],
    [
        'number' => '02',
        'title' => 'Missing descriptions',
        'detail' => 'Items with no description',
        'count' => $missingDescriptions,
        'link' => 'items.php'
    ],
    [
        'number' => '03',
        'title' => 'Empty categories',
        'detail' => 'Categories without products',
        'count' => $emptyCategories,
        'link' => 'categories.php'
    ],
    [
        'number' => '04',
        'title' => 'Hidden by category status',
        'detail' => 'Active items in inactive categories',
        'count' => $hiddenByCategory,
        'link' => 'categories.php'
    ]
];

$checksNeedingAttention = 0;

foreach ($attentionChecks as $check) {
    if ($check['count'] > 0) {
        $checksNeedingAttention++;
    }
}

require 'includes/header.php';
?>

<div class="brew-dashboard">

  <!-- PAGE HEADER -->
  <header class="db-page-header">

    <div class="db-eyebrow">
      <span class="db-blue-line"></span>
      BREW CO. / BACK OFFICE
    </div>

    <div class="db-page-header-inner">
      <div>
        <h1>Your menu, <em>at a glance.</em></h1>
      </div>

      <a href="../index.php"
         target="_blank"
         rel="noopener noreferrer"
         class="db-top-link">
        View customer menu ↗
      </a>
    </div>

  </header>

  <!-- OVERVIEW -->
  <section class="db-section">

    <div class="db-section-heading">
      <span class="db-section-index">
        01 / THE OVERVIEW
      </span>
      <h2>The essentials</h2>
    </div>

    <div class="db-overview-grid">

      <!-- Featured Statistic -->
      <article class="db-feature-card">

        <div class="db-feature-top">
          <span class="db-card-caption">
            TOTAL MENU ITEMS
          </span>
          <span class="db-feature-tiny-mark">B/C</span>
        </div>

        <div class="db-feature-value">
          <?= number_format($totalItems) ?>
        </div>

        <div class="db-feature-bottom">
          <span class="db-feature-rule"></span>
          <p>
            Products currently managed in
            Brew Co.'s digital menu.
          </p>
        </div>

      </article>

      <!-- Smaller Statistics -->
      <div class="db-mini-grid">

        <article class="db-mini-card db-mini-card-blue">
          <div class="db-mini-top">
            <span>CUSTOMER-VISIBLE ITEMS</span>
            <span class="db-mini-index">01</span>
          </div>

          <div class="db-mini-value">
            <?= number_format($visibleItems) ?>
          </div>

          <p>Active items in active categories</p>
        </article>

        <article class="db-mini-card">
          <div class="db-mini-top">
            <span>CATEGORIES</span>
            <span class="db-mini-index">02</span>
          </div>

          <div class="db-mini-value">
            <?= number_format($totalCategories) ?>
          </div>

          <p>Ways your menu is organised</p>
        </article>

        <article class="db-mini-card">
          <div class="db-mini-top">
            <span>INACTIVE ITEMS</span>
            <span class="db-mini-index">03</span>
          </div>

          <div class="db-mini-value">
            <?= number_format($inactiveItems) ?>
          </div>

          <p>Products switched off manually</p>
        </article>

      </div>
    </div>

  </section>

  <!-- LOWER DASHBOARD -->
  <div class="db-lower-grid">

    <!-- MENU HEALTH -->
    <section class="db-panel db-attention-panel">

      <div class="db-panel-top">
        <span class="db-section-index">
          02 / MENU HEALTH
        </span>

        <?php if ($checksNeedingAttention > 0): ?>
          <span class="db-health-status db-health-status-review">
            <?= $checksNeedingAttention ?>
            <?= $checksNeedingAttention === 1 ? 'check' : 'checks' ?>
            to review
          </span>
        <?php else: ?>
          <span class="db-health-status db-health-status-clear">
            All checks clear
          </span>
        <?php endif; ?>
      </div>

      <h2>
        Needs your attention<span class="db-blue-period">.</span>
      </h2>

      <p class="db-panel-intro">
        Small details that can make your customer menu better.
      </p>

      <div class="db-check-list">

        <?php foreach ($attentionChecks as $check): ?>

          <div class="db-check-row">

            <span class="db-check-number">
              <?= htmlspecialchars($check['number']) ?>
            </span>

            <div class="db-check-details">
              <h3>
                <?= htmlspecialchars($check['title']) ?>
              </h3>
              <p>
                <?= htmlspecialchars($check['detail']) ?>
              </p>
            </div>

            <span class="db-check-quantity <?= $check['count'] > 0 ? 'db-check-has-issues' : 'db-check-clear' ?>">
              <?= number_format($check['count']) ?>
            </span>

            <?php if ($check['count'] > 0): ?>
              <a class="db-check-link"
                 href="<?= htmlspecialchars($check['link']) ?>">
                Review ↗
              </a>
            <?php else: ?>
              <span class="db-check-done">Clear</span>
            <?php endif; ?>

          </div>

        <?php endforeach; ?>

      </div>

      <p class="db-panel-note">
        Counts update automatically from your menu database.
      </p>

    </section>

    <!-- QUICK ACTIONS -->
    <aside class="db-panel db-actions-panel">

      <span class="db-section-index">
        03 / SHORTCUTS
      </span>

      <h2>
        Make a change<span class="db-blue-period">.</span>
      </h2>

      <p class="db-panel-intro">
        The tasks you come back for most often.
      </p>

      <nav class="db-actions-list">

        <a href="items.php" class="db-action db-action-primary">
          <span class="db-action-symbol">+</span>
          <span class="db-action-text">
            <strong>Add a menu item</strong>
            <small>Create a new product</small>
          </span>
          <span class="db-action-arrow">↗</span>
        </a>

        <a href="categories.php" class="db-action">
          <span class="db-action-symbol">02</span>
          <span class="db-action-text">
            <strong>Manage categories</strong>
            <small>Add, edit or change visibility</small>
          </span>
          <span class="db-action-arrow">↗</span>
        </a>

        <a href="items.php" class="db-action">
          <span class="db-action-symbol">03</span>
          <span class="db-action-text">
            <strong>Manage all items</strong>
            <small>Edit prices, photos and details</small>
          </span>
          <span class="db-action-arrow">↗</span>
        </a>

      </nav>

      <div class="db-actions-bottom">
        <span class="db-actions-dot"></span>
        Powered by your live menu data
      </div>

    </aside>

  </div>

</div>

<?php require 'includes/footer.php'; ?>