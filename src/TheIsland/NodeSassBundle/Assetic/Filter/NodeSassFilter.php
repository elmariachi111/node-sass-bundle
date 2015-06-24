<?php
namespace TheIsland\NodeSassBundle\Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Exception\FilterException;
use Assetic\Filter\Sass\BaseSassFilter;

class NodeSassFilter extends BaseSassFilter
{
    const STYLE_NESTED     = 'nested';
    const STYLE_EXPANDED   = 'expanded';
    const STYLE_COMPACT    = 'compact';
    const STYLE_COMPRESSED = 'compressed';

    private $sassPath;
    private $rubyPath;
    private $style;
    private $debugInfo;
    private $sourceMap;

    public function __construct($sassPath = '/usr/bin/node-sass', $rubyPath = null)
    {
        $this->sassPath = $sassPath;
        $this->rubyPath = $rubyPath;
    }

    public function setStyle($style)
    {
        $this->style = $style;
    }

    public function setSourceMap($sourceMap)
    {
        $this->sourceMap = $sourceMap;
    }

    public function setDebugInfo($debugInfo)
    {
        $this->debugInfo = $debugInfo;
    }

    public function filterLoad(AssetInterface $asset)
    {
        $sassProcessArgs = array($this->sassPath);
        if (null !== $this->rubyPath) {
            $sassProcessArgs = array_merge(explode(' ', $this->rubyPath), $sassProcessArgs);
        }

        $pb = $this->createProcessBuilder($sassProcessArgs);

        if ($dir = $asset->getSourceDirectory()) {
            $pb->add('--include-path')->add($dir);
        }

        if ($this->style) {
            $pb->add('--output-style')->add($this->style);
        }

        if ($this->sourceMap) {
            $pb->add('--source-map');
        }

        if ($this->debugInfo) {
            $pb->add('--source-comments');
        }

        foreach ($this->loadPaths as $loadPath) {
            $pb->add('--include-path')->add($loadPath);
        }

        $pb->add('--stdout');

        // input
        $pb->add($input = tempnam(sys_get_temp_dir(), 'assetic_sass'));
        file_put_contents($input, $asset->getContent());

        $proc = $pb->getProcess();
        $code = $proc->run();
        unlink($input);

        if (0 !== $code) {
            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        $asset->setContent($proc->getOutput());
    }

    public function filterDump(AssetInterface $asset)
    {
    }
}