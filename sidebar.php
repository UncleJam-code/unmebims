<?php
// Load the XML file
$xml = simplexml_load_file('sidebar.xml');
if ($xml === false) {
    die("Error: Cannot load XML file");
}
?>

<div class="sidebar">
    <h2><?= htmlspecialchars((string)$xml->header->title) ?></h2>
    <ul>
        <?php foreach ($xml->navigation->children() as $element): ?>
            <?php if ($element->getName() === 'item'): ?>
                <!-- Render single navigation item -->
                <li>
                    <a href="<?= htmlspecialchars((string)$element->link) ?>">
                        <i class="<?= htmlspecialchars((string)$element->icon) ?>"></i>
                        <?= htmlspecialchars((string)$element->label) ?>
                    </a>
                </li>
            <?php elseif ($element->getName() === 'dropdown'): ?>
                <!-- Render dropdown menu -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" onclick="event.preventDefault();">
                        <i class="<?= htmlspecialchars((string)$element->icon) ?>"></i>
                        <?= htmlspecialchars((string)$element->label) ?> <i class="fas fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <?php foreach ($element->items->item as $subItem): ?>
                            <li>
                                <a href="<?= htmlspecialchars((string)$subItem->link) ?>">
                                    <?= htmlspecialchars((string)$subItem->label) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Dropdown Toggle Script -->
<script>
document.querySelectorAll('.dropdown-toggle').forEach(item => {
    item.addEventListener('click', event => {
        // Prevent the default behavior of the link
        event.preventDefault();
        const parent = item.parentElement;
        parent.classList.toggle('active'); // Toggle the active class for dropdown menus
    });
});
</script>