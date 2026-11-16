<?php
require '../config/db.php';

$page = 'categories';
$page_title = 'Categories';

$perPage = 5;
$q = trim($_GET['q'] ?? '');
$p = max(1, (int)($_GET['page'] ?? 1));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $msg    = '';

    if ($action === 'save') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $error = 'Name is required.';
        } else {
            if ($id > 0) {
                $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?")->execute([$name, $id]);
            } else {
                $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
            }
            $msg = 'saved';
        }
    } elseif ($action === 'toggle' && $id > 0) {
        $pdo->prepare("UPDATE categories SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
        $msg = 'toggled';
    } elseif ($action === 'delete' && $id > 0) {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        $msg = 'deleted';
    }

    if ($msg !== '') {
        header('Location: categories.php?' . http_build_query(['q' => $q, 'page' => $p, 'msg' => $msg]));
        exit;
    }
}

$messages = [
    'saved'   => 'Category saved.',
    'toggled' => 'Status updated.',
    'deleted' => 'Category deleted.',
];
$flash = $messages[$_GET['msg'] ?? ''] ?? '';


$formId   = (int)($_GET['edit'] ?? 0);
$formName = '';
if ($formId > 0) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$formId]);
    $formName = $stmt->fetchColumn() ?: '';
    if ($formName === '') {
        $formId = 0;
    }
}

$like = '%' . $q . '%';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name LIKE ?");
$stmt->execute([$like]);
$total = (int)$stmt->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));
$p = min($p, $totalPages);
$offset = ($p - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT c.*,
            (SELECT COUNT(*) FROM items i WHERE i.category_id = c.id) AS items_count
     FROM categories c
     WHERE c.name LIKE ?
     ORDER BY c.id
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute([$like]);
$categories = $stmt->fetchAll();

$baseQs = http_build_query(['q' => $q, 'page' => $p]);

require 'includes/header.php';
?>

<h1 class="h3 mb-4">Categories</h1>

<?php if ($flash): ?>
  <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
  <!-- Add / Edit form -->
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5 mb-3"><?= $formId ? 'Edit category' : 'Add category' ?></h2>

        <form method="post" action="categories.php?<?= $baseQs ?>">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= $formId ?>">

          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input
              type="text"
              id="name"
              name="name"
              class="form-control"
              maxlength="100"
              required
              value="<?= htmlspecialchars($formName) ?>"
            />
          </div>

          <button class="btn btn-brew">Save</button>
          <?php if ($formId): ?>
            <a href="categories.php?<?= $baseQs ?>" class="btn btn-outline-secondary">Cancel</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <!-- Categories list -->
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <form method="get" class="row g-2 mb-3">
          <div class="col">
            <input
              type="search"
              name="q"
              class="form-control"
              placeholder="Search categories..."
              value="<?= htmlspecialchars($q) ?>"
            />
          </div>
          <div class="col-auto">
            <button class="btn btn-brew">Search</button>
            <?php if ($q !== ''): ?>
              <a href="categories.php" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
          </div>
        </form>

        <div class="text-muted small mb-2"><?= $total ?> categories found</div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Items</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $c): ?>
                <tr>
                  <td><?= $c['id'] ?></td>
                  <td><?= htmlspecialchars($c['name']) ?></td>
                  <td><?= (int)$c['items_count'] ?></td>
                  <td>
                    <span class="badge <?= $c['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                      <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                  </td>
                  <td class="text-end text-nowrap">
                    <a
                      class="btn btn-sm btn-outline-secondary"
                      href="categories.php?<?= http_build_query(['edit' => $c['id'], 'q' => $q, 'page' => $p]) ?>"
                    >Edit</a>

                    <form method="post" action="categories.php?<?= $baseQs ?>" class="d-inline">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <button class="btn btn-sm btn-outline-secondary">
                        <?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>
                      </button>
                    </form>

                    <form
                      method="post"
                      action="categories.php?<?= $baseQs ?>"
                      class="d-inline"
                      onsubmit="return confirm('Delete this category and its <?= (int)$c['items_count'] ?> items?');"
                    >
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (!$categories): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">No categories found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($totalPages > 1): ?>
          <nav>
            <ul class="pagination justify-content-center mb-0">
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $p ? 'active' : '' ?>">
                  <a class="page-link" href="categories.php?<?= http_build_query(['q' => $q, 'page' => $i]) ?>">
                    <?= $i ?>
                  </a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>