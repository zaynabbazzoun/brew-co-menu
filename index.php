<?php
require __DIR__ . '/config/db.php';


$categories = $pdo->query(
    'SELECT id, name, icon
     FROM categories
     WHERE is_active = 1
     ORDER BY id'
)->fetchAll(PDO::FETCH_ASSOC);

$items = $pdo->query(
    'SELECT i.*
     FROM items i
     INNER JOIN categories c
       ON c.id = i.category_id
     WHERE i.is_active = 1
       AND c.is_active = 1
     ORDER BY i.category_id, i.id'
)->fetchAll(PDO::FETCH_ASSOC);

$itemsByCategory = [];

foreach ($items as $item) {
    $itemsByCategory[(int) $item['category_id']][] = $item;
}
$showCategoryOnly = isset($_GET['category']);
$selectedCategory = null;

if (
    $showCategoryOnly &&
    is_string($_GET['category'])
) {
    $selectedId = filter_var(
        $_GET['category'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($selectedId !== false) {
        foreach ($categories as $category) {
            if ((int) $category['id'] === $selectedId) {
                $selectedCategory = $category;
                break;
            }
        }
    }
}

// Escape text before displaying it in HTML
function menuText($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
  />

  <title>
    <?= $selectedCategory
        ? menuText($selectedCategory['name']) . ' | '
        : '' ?>Brew Co. Menu
  </title>

  <link rel="stylesheet" href="styles.css?v=2" />
</head>

<body>

  <!-- BRAND HEADER -->
  <header class="site-header">
    <a
      href="index.php"
      class="brand-link"
      aria-label="Brew Co. home"
    >
      <img
        src="images/icon-logo.svg"
        alt=""
        class="logo-icon"
      />

      <span class="brand-name">BREW CO.</span>
      <span class="brand-tagline">Coffee &amp; More</span>
    </a>
  </header>

  <main>

    <!-- HOME PAGE HERO -->
    <?php if (!$showCategoryOnly): ?>

      <section
        class="hero"
        aria-label="Welcome to Brew Co."
      >
        <div class="hero-content">
          <h1>
            Good Coffee,<br />
            <span>Good Day.</span>
          </h1>

          <p>Made with love, just for you.</p>
        </div>
      </section>

    <!-- SELECTED CATEGORY PAGE -->
    <?php elseif ($selectedCategory): ?>

      <section class="category-intro">
        <a href="index.php#menu" class="back-link">
          ← Back to full menu
        </a>

        <span class="section-kicker">
          BREW CO. / OUR SELECTION
        </span>

        <h1>
          <?= menuText($selectedCategory['name']) ?>
        </h1>

        <p>
          Take your time. Find something you'll love.
        </p>
      </section>

    <?php endif; ?>


    <!-- MENU -->
    <section class="menu" id="menu">

      <?php if (!$showCategoryOnly): ?>

        <div class="menu-heading">
          <span class="section-kicker">
            HANDPICKED FOR YOUR DAY
          </span>

          <h2>Our Menu</h2>

          <p>Something good for every craving.</p>
        </div>

      <?php endif; ?>


      <!-- INVALID OR INACTIVE CATEGORY -->
      <?php if (
          $showCategoryOnly &&
          !$selectedCategory
      ): ?>

        <div class="empty-state">
          <h1>Category not available</h1>

          <p>
            This category may have been removed
            or hidden.
          </p>

          <a href="index.php#menu" class="back-link">
            ← See the full menu
          </a>
        </div>

      <?php else: ?>

        <?php
          $visibleCategories = $showCategoryOnly
              ? [$selectedCategory]
              : $categories;
        ?>

        <?php foreach ($visibleCategories as $category): ?>

          <?php
            $categoryId = (int) $category['id'];

            $categoryItems =
                $itemsByCategory[$categoryId] ?? [];

            $shownItems = $showCategoryOnly
                ? $categoryItems
                : array_slice($categoryItems, 0, 3);

            $categoryIcon = basename(
                (string) ($category['icon'] ?? '')
            );

            $iconExists =
                $categoryIcon !== '' &&
                is_file(
                    __DIR__ . '/images/' . $categoryIcon
                );
          ?>

          <section
            class="menu-category"
            aria-labelledby="category-<?= $categoryId ?>"
          >

            <div class="category-heading">

              <div class="category-heading-left">

                <?php if ($iconExists): ?>

                  <img
                    src="images/<?= menuText(rawurlencode($categoryIcon)) ?>"
                    alt=""
                    class="category-icon"
                  />

                <?php else: ?>

                  <span
                    class="category-symbol"
                    aria-hidden="true"
                  >•</span>

                <?php endif; ?>

                <h3 id="category-<?= $categoryId ?>">
                  <?= menuText($category['name']) ?>
                </h3>

                <span class="category-count">
                  <?= count($categoryItems) ?> items
                </span>

              </div>


              <?php if (
                  !$showCategoryOnly &&
                  count($categoryItems) > 0
              ): ?>

                <a
                  href="index.php?category=<?= $categoryId ?>"
                  class="view-all"
                  aria-label="View all <?= menuText($category['name']) ?> items"
                >
                  View All
                  <span aria-hidden="true">↗</span>
                </a>

              <?php endif; ?>

            </div>

            <?php if ($shownItems): ?>

              <div class="menu-items">

                <?php foreach ($shownItems as $item): ?>

                  <?php
                    $imageFile = basename(
                        trim(
                            (string) ($item['image'] ?? '')
                        )
                    );

                    $imagePath =
                        __DIR__ . '/images/' . $imageFile;

                    $hasImage =
                        $imageFile !== '' &&
                        is_file($imagePath) &&
                        filesize($imagePath) > 0;
                  ?>

                  <article class="menu-item">


                    <?php if ($hasImage): ?>

                      <img
                        src="images/<?= menuText(rawurlencode($imageFile)) ?>"
                        alt="<?= menuText($item['name']) ?>"
                        class="item-image"
                        loading="lazy"
                      />

                    <?php else: ?>

                      <div
                        class="item-image item-image-fallback"
                        role="img"
                        aria-label="Photo not available"
                      >
                        <span>BREW<br />CO.</span>
                      </div>

                    <?php endif; ?>

                    <div class="item-details">

                      <h4>
                        <?= menuText($item['name']) ?>
                      </h4>

                      <?php if (
                          trim(
                              (string) (
                                  $item['description'] ?? ''
                              )
                          ) !== ''
                      ): ?>

                        <p>
                          <?= menuText(
                              $item['description']
                          ) ?>
                        </p>

                      <?php endif; ?>

                    </div>


                    <p class="item-price">
                      $<?= number_format(
                          (float) $item['price'],
                          2
                      ) ?>
                    </p>

                  </article>

                <?php endforeach; ?>

              </div>

            <?php else: ?>

              <p class="category-empty">
                Nothing in this category just yet.
              </p>

            <?php endif; ?>

          </section>

        <?php endforeach; ?>


        <?php if (!$categories): ?>

          <div class="empty-state">
            <p>
              Our menu is being updated.
              Check back soon!
            </p>
          </div>

        <?php endif; ?>

      <?php endif; ?>

    </section>


    <?php if (!$showCategoryOnly): ?>

      <aside class="delivery-banner">

        <img
          src="images/icon-scooter.svg"
          alt=""
          class="delivery-icon"
        />

        <div class="delivery-text">
          <p>We deliver to you!</p>
          <p>Fast &amp; fresh to your door.</p>
        </div>

        <span class="delivery-note">
          MADE WITH CARE
        </span>

      </aside>

    <?php endif; ?>

  </main>

</body>
</html>
