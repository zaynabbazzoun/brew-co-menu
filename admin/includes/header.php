<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($page_title ?? 'Admin') ?> | Brew Co. Admin</title>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="admin.css?v=<?= filemtime(__DIR__ . '/../admin.css') ?>" />
  </head>

  <body>
    <!-- phones and tablets only -->
    <nav class="navbar bg-white border-bottom d-lg-none">
      <div class="container-fluid">
        <span class="navbar-brand fw-bold">BREW CO. Admin</span>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#sidebarMenu"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
      </div>
    </nav>

    <div class="row g-0">
      <aside class="col-lg-3 col-xl-2 collapse d-lg-block sidebar p-3" id="sidebarMenu">
        <div class="brand text-center py-3 d-none d-lg-block">BREW CO.</div>

        <ul class="nav flex-column gap-1">
          <li class="nav-item">
            <a class="nav-link <?= ($page ?? '') === 'dashboard' ? 'active' : '' ?>" href="index.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= ($page ?? '') === 'categories' ? 'active' : '' ?>" href="categories.php">Categories</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= ($page ?? '') === 'items' ? 'active' : '' ?>" href="items.php">Items</a>
          </li>
          <li class="nav-item mt-3">
            <a class="nav-link" href="../index.php" target="_blank">View menu ↗</a>
          </li>

<li class="nav-item">
  <form method="post" action="logout.php">
    <input
      type="hidden"
      name="csrf_token"
      value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>"
    >
    <button
      type="submit"
      class="nav-link w-100 text-start border-0 bg-transparent"
    >
      Log out
    </button>
  </form>
</li>

        </ul>
      </aside>

      <main class="col-lg-9 col-xl-10 p-4">