<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HomePageHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(HomePageHandler::class)]
#[CoversMethod(HomePageHandler::class, 'handle')]
final class HomePageHandlerTest extends TestCase
{
    public function testReturnsHtmlResponseWhenTemplateRendererProvided(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with('app::home-page', $this->isArray())
            ->willReturn('');

        $homePage = new HomePageHandler(
            $renderer,
        );

        $response = $homePage->handle(
            $this->createStub(ServerRequestInterface::class),
        );

        self::assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testReturnsJsonResponseWhenNoTemplateRendererProvided(): void
    {
        $homePage = new HomePageHandler(
            null,
        );
        $response = $homePage->handle(
            $this->createStub(ServerRequestInterface::class),
        );

        self::assertInstanceOf(JsonResponse::class, $response);
    }
}
