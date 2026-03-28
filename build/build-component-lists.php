<?php

declare(strict_types=1);

const PROJECT_INFO = [
    'mezzio' => [
        'title'    => 'Mezzio',
        'subtitle' => 'PSR-15 Middleware in Minutes',
        'file'     => 'data/component-list.mezzio.json',
    ],
];

const GROUP_TEMPLATE = <<< 'END'
<h4 id="{anchor}">{name}</h4>
<div class="row row-cols-1 row-cols-md-2">
{packages}
</div>
END;

const CARD_TEMPLATE = <<< 'END'
<div class="col mb-4">
    <div class="card h-100">
        <a href="{url}">
            <div class="card-header">
                {package}
            </div>
            <div class="card-body">
                <h5 class="card-title">{name}</h5>
                <p class="card-text">{description}</p>
            </div>
        </a>
    </div>
</div>

END;

const DECK_TEMPLATE = <<< 'END'
<h3 class="display-4">Documentation of Mezzio<br>
    <small class="text-muted">PSR-15 Middleware in Minutes</small>
</h3>
<hr>
{toc}
{content}
END;

const TOC = <<< 'END'
<div class="toc">
    <h6 class="toc__headline">On this page</h6>
    <ul class="toc__list">{items}</ul>
</div>
END;

const TOC_ITEM = <<< 'END'
<li class="toc__entry">
    <a href="#{anchor}" class="toc__link nav-link">{name}</a>
</li>
END;


function preparePackage(array $package) : string
{
    $card = CARD_TEMPLATE;
    foreach ($package as $key => $value) {
        $search = sprintf('{%s}', $key);
        $card = str_replace($search, $value, $card);
    }
    return $card;
}

function prepareGroup(string $name, array $packages) : string
{
    $htmlBlocks = array_map(static function ($package) {
        return preparePackage($package);
    }, $packages);

    return str_replace(
        [
            '{name}',
            '{packages}',
            '{anchor}',
            '<h4 id=""></h4>',
        ],
        [
            $name,
            implode("\n", $htmlBlocks),
            filterAnchorName($name),
            '',
        ],
        GROUP_TEMPLATE
    );
}

function prepareProject(array $project) : string
{
    $groupedPackages = groupPackages($project);

    $html = '';
    foreach ($groupedPackages as $group => $packages) {
        $html .= prepareGroup($group, $packages);
    }

    return str_replace(
        [
            '{content}',
            '{toc}',
        ],
        [
            $html,
            createTableOfContents($project)
        ],
        DECK_TEMPLATE
    );
}

function fetchProject(string $file) : array
{
    $contents = file_get_contents($file);
    return json_decode($contents, true);
}

function injectProjectContent(string $content, string $file) : void
{
    $homepage = file_get_contents($file);
    $replacement = preg_replace(
        [
            '#(?<start>\<\!-- START COMPONENT LISTS --\>).*?(?<end>\<\!-- END COMPONENT LISTS --\>)#s',
            '#(?<start>\<\!-- START YEAR --\>).*?(?<end>\<\!-- END YEAR --\>)#s',
        ],
        [
            '$1' . $content . '$2',
            '${1}' . date('Y') . '${2}',
        ],
        $homepage
    );

    file_put_contents($file, $replacement);
}

function groupPackages(array $project) : array
{
    $groupedPackages = [];
    foreach ($project as $package) {
        $groupedPackages[$package['group']][] = $package;
    }
    ksort($groupedPackages);

    return $groupedPackages;
}

function createTableOfContents(array $project) : string
{
    $groupedPackages = groupPackages($project);

    $html = '';
    foreach ($groupedPackages as $group => $packages) {
        if (empty($group)) {
            continue;
        }

        $html .= prepareTocItem($group);
    }

    return str_replace(
        '{items}',
        $html,
        TOC
    );
}

function prepareTocItem(string $name) : string
{
    return str_replace(
        [
            '{anchor}',
            '{name}',
        ],
        [
            filterAnchorName($name),
            $name,
        ],
        TOC_ITEM
    );
}

function filterAnchorName(string $name) : string
{
    return str_replace(' ', '-', strtolower($name));
}

chdir(dirname(__DIR__));

$content = prepareProject(fetchProject(PROJECT_INFO['mezzio']['file']));

injectProjectContent($content, './index.html');
