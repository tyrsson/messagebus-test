<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HomePageHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(HomePageHandler::class)]
#[CoversMethod(HomePageHandler::class, 'handle')]
final class HomePageHandlerTest extends TestCase
{
    public function testReturnsHtmlResponse(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with('app::home-page')
            ->willReturn('<html lang="en-US"><body>home</body></html>');

        $homePage = new HomePageHandler($renderer);

        $response = $homePage->handle(
            $this->createStub(ServerRequestInterface::class),
        );

        self::assertInstanceOf(HtmlResponse::class, $response);
    }
}
