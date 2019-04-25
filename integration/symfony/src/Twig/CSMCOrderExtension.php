<?php

namespace App\Twig;

use App\Helpers\FileManager;
use Symfony\Component\Routing\RouterInterface;

class CSMCOrderExtension extends \Twig_Extension
{
    const ASC = 'asc';
    const DESC = 'desc';
    const ICON = [self::ASC => 'up', self::DESC => 'down'];

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * CSMCOrderExtension constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function CSMCorder(\Twig_Environment $environment, FileManager $fileManager, $type)
    {
        $order = self::ASC === $fileManager->getQueryParameter('order');
        $active = $fileManager->getQueryParameter('orderby') === $type ? 'actived' : null;
        $orderBy = [];
        $orderBy['orderby'] = $type;
        $orderBy['order'] = $active ? ($order ? self::DESC : self::ASC) : self::ASC;
        $parameters = array_merge($fileManager->getQueryParameters(), $orderBy);

        $icon = $active ? '-' . ($order ? self::ICON[self::ASC] : self::ICON[self::DESC]) : '';

        $href = $this->router->generate('file_manager', $parameters);

        return $environment->render('fileManager/extension/_order.html.twig', [
            'active' => $active,
            'href' => $href,
            'icon' => $icon,
            'type' => $type,
            'islist' => 'list' === $fileManager->getView(),
        ]);
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            'CSMCorder' => new \Twig_SimpleFunction('CSMCorder', [$this, 'CSMCorder'],
                ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }
}
