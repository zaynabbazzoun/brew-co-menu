<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

require '../config/db.php';

$page = 'items';
$page_title = 'Items';

$perPage = 8;
$q = trim($_GET['q'] ?? '');
$p = max(1, (int)($_GET['page'] ?? 1));
$error = '';


function uploadImage(array $file, string &$error): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Image upload failed. Please try again.';
        return null;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $error = 'Image must be smaller than 2 MB.';
        return null;
    }

    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if ($info === false || !isset($allowed[$info[2]])) {
        $error = 'Only JPG, PNG or WEBP images are allowed.';
        return null;
    }


    $filename = 'item-' . bin2hex(random_bytes(6)) . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../images/' . $filename)) {
        $error = 'Could not save the image.';
        return null;
    }
    return $filename;
}

$form = ['id' => 0, 'category_id' => '', 'name' => '', 'description' => '', 'price' => '', 'image' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        exit('Invalid security token. Please refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $msg    = '';

    if ($action === 'save') {
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priceRaw    = trim($_POST['price'] ?? '');
        $newImage    = null;

        // When editing, load the current row (we keep its image if no new one is chosen)
        $existing = null;
        if ($id > 0) {
            $s = $pdo->prepare("SELECT * FROM items WHERE id = ?");
            $s->execute([$id]);
            $existing = $s->fetch() ?: null;
            if (!$existing) {
                $id = 0;
            }
        }

        $s = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
        $s->execute([$categoryId]);

        if ((int)$s->fetchColumn() === 0) {
            $error = 'Please choose a category.';
        } elseif ($name === '' || mb_strlen($name) > 100) {
            $error = 'Name is required (max 100 characters).';
        } elseif (mb_strlen($description) > 255) {
            $error = 'Description must be 255 characters or less.';
        } elseif (!is_numeric($priceRaw) || $priceRaw < 0 || $priceRaw > 9999.99) {
            $error = 'Please enter a valid price.';
        } else {
            $newImage = uploadImage($_FILES['image'] ?? [], $error);
        }

        if ($error === '') {
            $image = $newImage ?? ($existing['image'] ?? null);

            if ($id > 0) {
                $pdo->prepare(
                    "UPDATE items SET category_id = ?, name = ?, description = ?, price = ?, image = ? WHERE id = ?"
                )->execute([$categoryId, $name, $description, $priceRaw, $image, $id]);
            } else {
                $pdo->prepare(
                    "INSERT INTO items (category_id, name, description, price, image) VALUES (?, ?, ?, ?, ?)"
                )->execute([$categoryId, $name, $description, $priceRaw, $image]);
            }
            $msg = 'saved';
        } else {

            $form = [
                'id'          => $id,
                'category_id' => $categoryId,
                'name'        => $name,
                'description' => $description,
                'price'       => $priceRaw,
                'image'       => $existing['image'] ?? '',
            ];
        }
    } elseif ($action === 'toggle' && $id > 0) {
        $pdo->prepare("UPDATE items SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
        $msg = 'toggled';
    } elseif ($action === 'delete' && $id > 0) {
        $pdo->prepare("DELETE FROM items WHERE id = ?")->execute([$id]);
        $msg = 'deleted';
    }

    if ($msg !== '') {
        header('Location: items.php?' . http_build_query(['q' => $q, 'page' => $p, 'msg' => $msg]));
        exit;
    }
}

$messages = [
    'saved'   => 'Item saved.',
    'toggled' => 'Status updated.',
    'deleted' => 'Item deleted.',
];
$flash = $messages[$_GET['msg'] ?? ''] ?? '';


$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $s = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $s->execute([$editId]);
    $row = $s->fetch();
    if ($row) {
        $form = $row;
    }
}
$formId = (int)$form['id'];


$allCategories = $pdo->query("SELECT id, name, is_active FROM categories ORDER BY name")->fetchAll();


$like = '%' . $q . '%';

$s = $pdo->prepare("SELECT COUNT(*) FROM items WHERE name LIKE ?");
$s->execute([$like]);
$total = (int)$s->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));
$p = min($p, $totalPages);
$offset = ($p - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT i.*, c.name AS category_name
     FROM items i
     JOIN categories c ON c.id = i.category_id
     WHERE i.name LIKE ?
     ORDER BY i.id
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute([$like]);
$items = $stmt->fetchAll();

$baseQs = http_build_query(['q' => $q, 'page' => $p]);

require 'includes/header.php';
?>

<h1 class="h3 mb-4">Items</h1>

<?php if ($flash): ?>
  <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!$allCategories): ?>
  <div class="alert alert-warning">
    There are no categories yet. <a href="categories.php">Add a category first</a>.
  </div>
<?php endif; ?>

<!-- Add / Edit form -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h2 class="h5 mb-3"><?= $formId ? 'Edit item' : 'Add item' ?></h2>

    <form method="post" action="items.php?<?= $baseQs ?>" enctype="multipart/form-data" class="row g-3">

    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $formId ?>">

      <div class="col-md-4">
        <label for="category_id" class="form-label">Category</label>
        <select id="category_id" name="category_id" class="form-select js-search" required>
          <option value="">Choose...</option>
          <?php foreach ($allCategories as $c): ?>
            <option
              value="<?= $c['id'] ?>"
              <?= (int)$form['category_id'] === (int)$c['id'] ? 'selected' : '' ?>
            >
              <?= htmlspecialchars($c['name']) ?><?= $c['is_active'] ? '' : ' (inactive)' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-5">
        <label for="name" class="form-label">Name</label>
        <input
          type="text"
          id="name"
          name="name"
          class="form-control"
          maxlength="100"
          required
          value="<?= htmlspecialchars($form['name']) ?>"
        />
      </div>

      <div class="col-md-3">
        <label for="price" class="form-label">Price ($)</label>
        <input
          type="number"
          id="price"
          name="price"
          class="form-control"
          step="0.01"
          min="0"
          max="9999.99"
          required
          value="<?= htmlspecialchars((string)$form['price']) ?>"
        />
      </div>

      <div class="col-md-8">
        <label for="description" class="form-label">Description</label>
        <input
          type="text"
          id="description"
          name="description"
          class="form-control"
          maxlength="255"
          value="<?= htmlspecialchars((string)$form['description']) ?>"
        />
      </div>

      <div class="col-md-4">
        <label for="image" class="form-label">Image</label>
        <input
          type="file"
          id="image"
          name="image"
          class="form-control"
          accept="image/jpeg,image/png,image/webp"
        />
        <?php if ($formId && $form['image']): ?>
          <div class="d-flex align-items-center gap-2 mt-2">
            <img src="../images/<?= htmlspecialchars($form['image']) ?>" alt="" class="thumb" />
            <span class="text-muted small">Leave empty to keep the current image.</span>
          </div>
        <?php endif; ?>
      </div>

      <div class="col-12">
        <button class="btn btn-brew">Save</button>
        <?php if ($formId): ?>
          <a href="items.php?<?= $baseQs ?>" class="btn btn-outline-secondary">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Items list -->
<div class="card shadow-sm">
  <div class="card-body">
    <form method="get" class="row g-2 mb-3">
      <div class="col">
        <input
          type="search"
          name="q"
          class="form-control"
          placeholder="Search items..."
          value="<?= htmlspecialchars($q) ?>"
        />
      </div>
      <div class="col-auto">
        <button class="btn btn-brew">Search</button>
        <?php if ($q !== ''): ?>
          <a href="items.php" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <div class="text-muted small mb-2"><?= $total ?> items found</div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $i): ?>
            <tr>
              <td>
                <img
                  src="../images/<?= htmlspecialchars($i['image'] ?: 'placeholder.jpg') ?>"
                  alt=""
                  class="thumb"
                />
              </td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($i['name']) ?></div>
                <div class="text-muted small"><?= htmlspecialchars((string)$i['description']) ?></div>
              </td>
              <td><?= htmlspecialchars($i['category_name']) ?></td>
              <td>$<?= number_format((float)$i['price'], 2) ?></td>
              <td>
                <span class="badge <?= $i['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                  <?= $i['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
              </td>
              <td class="text-end text-nowrap">
                <a
                  class="btn btn-sm btn-outline-secondary"
                  href="items.php?<?= http_build_query(['edit' => $i['id'], 'q' => $q, 'page' => $p]) ?>"
                >Edit</a>

                <form method="post" action="items.php?<?= $baseQs ?>" class="d-inline">

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $i['id'] ?>">
                  <button class="btn btn-sm btn-outline-secondary">
                    <?= $i['is_active'] ? 'Deactivate' : 'Activate' ?>
                  </button>
                </form>

                <form
                  method="post"
                  action="items.php?<?= $baseQs ?>"
                  class="d-inline"
                  onsubmit="return confirm('Delete this item?');"
                >
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $i['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (!$items): ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">No items found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav>
        <ul class="pagination justify-content-center mb-0">
          <?php for ($n = 1; $n <= $totalPages; $n++): ?>
            <li class="page-item <?= $n === $p ? 'active' : '' ?>">
              <a class="page-link" href="items.php?<?= http_build_query(['q' => $q, 'page' => $n]) ?>">
                <?= $n ?>
              </a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</div>

<?php require 'includes/footer.php'; ?>