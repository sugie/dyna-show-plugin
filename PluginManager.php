<?php


namespace Plugin\DynaShow;

use Eccube\Application;
use Eccube\Entity\Block;
use Eccube\Entity\BlockPosition;
use Eccube\Entity\Layout;
use Eccube\Entity\Master\DeviceType;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\BlockPositionRepository;
use Eccube\Repository\BlockRepository;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class PluginManager extends AbstractPluginManager
{

    /**
     * @var コピー元ブロックファイル
     */
    protected $sourceBlock;

    /**
     * @var string ブロック名
     */
    protected $blockName = 'DynaShow商品動的表示ブロック';

    /**
     * @var string ブロックファイル名
     */
    protected $blockTwigFileName = 'dynashow_block';

    /**
     * @param null $meta
     * @param Application|null $app
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        log_debug('#DS47: uninstall');
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function enable(array $meta = null, ContainerInterface $container)
    {
        log_debug('#DS57: enable');
        $this->makeTwigBlock($container);
        $this->makeDbBlock($container);
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     */

    public function disable(array $meta = null, ContainerInterface $container)
    {
        log_debug('#DS67: disable');
        $this->deleteTwigBlock($container);
        $this->deleteDbBlock($container);
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta = null, ContainerInterface $container)
    {
        log_debug('#DS77: update');
    }

    /**
     * DynaShow ブロックを作成
     * @param ContainerInterface $container
     */
    private function makeTwigBlock(ContainerInterface $container)
    {
        $filesystem = new Filesystem;
        if (!$filesystem->exists($this->getCopyToPath($container))) {
            $filesystem->copy($this->getCopyFromPath(), $this->getCopyToPath($container));
        }
    }

    /**
     * EC-CUBE内のdyna show block twig のコピー先パスを返す
     * @param ContainerInterface $container
     * @return string
     */
    private function getCopyToPath(ContainerInterface $container)
    {
        return $container->getParameter('eccube_theme_front_dir') . '/Block/' . $this->blockTwigFileName . '.twig';
    }

    /**
     * プラグイン内のブロックtwigのパスを返す
     * @return string
     */
    private function getCopyFromPath()
    {
        return __DIR__ . '/Resource/template/Block/' . $this->blockTwigFileName . '.twig';
    }

    /**
     * Dyna show twig を削除
     * @param ContainerInterface $container
     */
    private function deleteTwigBlock(ContainerInterface $container)
    {
        $filesystem = new Filesystem;
        $filesystem->remove($this->getCopyToPath($container));
    }

    /**
     * DBにDynaShow ブロックを作成
     * @param ContainerInterface $container
     */
    private function makeDbBlock(ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $deviceType = $container->get(DeviceTypeRepository::class)->find(DeviceType::DEVICE_TYPE_PC);
        try {
            $block = $container->get(BlockRepository::class)->newBlock($deviceType);
            $block->setName($this->blockName)
                ->setFileName($this->blockTwigFileName)
                ->setUseController(false)
                ->setDeletable(false);
            $em->persist($block);
            $em->flush($block);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function deleteDbBlock(ContainerInterface $container)
    {
        $block = $container->get(BlockRepository::class)
            ->findOneBy(['file_name' => $this->blockTwigFileName]);
        $em = $container->get('doctrine.orm.entity_manager');
        if (!empty($block)) {
            try {
                $em->remove($block);
                $em->flush();
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

}