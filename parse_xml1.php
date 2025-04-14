<?php
// Load the XML file
$xmlFile = 'unmebims.xml'; // file is in the same folder

if (!file_exists($xmlFile)) {
    die("Error: XML file not found at '$xmlFile'.");
}

$xml = simplexml_load_file($xmlFile);

if ($xml === false) {
    echo "Error loading XML:\n";
    foreach (libxml_get_errors() as $error) {
        echo "\t", $error->message;
    }
    libxml_clear_errors();
    exit();
}

// Function to render the sidebar menu
function renderSidebar($menu) {
    echo '<div class="sidebar">';
    echo '<h2>UNMEB1</h2>';
    echo '<ul>';

    // Render top-level menu items
    foreach ($menu->MenuItem as $menuItem) {
        $linkHref = (string)$menuItem->Link['href'];
        $iconClass = (string)$menuItem->Link->Icon['class'];
        $text = (string)$menuItem->Link->Text;
        echo "<li><a href='$linkHref'><i class='$iconClass'></i> $text</a></li>";
    }

    // Render dropdown menus
    foreach ($menu->Dropdown as $dropdown) {
        $label = (string)$dropdown['label'];
        echo "<li class='dropdown'>";
        echo "<a href='#' class='dropdown-toggle'><i class='fas fa-caret-down'></i> $label</a>";
        echo "<ul class='dropdown-menu'>";
        foreach ($dropdown->SubmenuItem as $submenu) {
            $submenuHref = (string)$submenu['href'];
            $submenuText = (string)$submenu;
            echo "<li><a href='$submenuHref'>$submenuText</a></li>";
        }
        echo "</ul></li>";
    }

    echo '</ul>';
    echo '</div>';
}

// Render the sidebar menu
renderSidebar($xml->Sidebar->Menu);
?>