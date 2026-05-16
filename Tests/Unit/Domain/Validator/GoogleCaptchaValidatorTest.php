<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\Domain\Validator;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use T3G\AgencyPack\Blog\Domain\Validator\GoogleCaptchaValidator;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GoogleCaptchaValidatorTest extends UnitTestCase
{
    #[Test]
    public function captchaDisabledDoesNotCallGoogleEndpoint(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')->willReturn([
            'comments' => [
                'google_recaptcha' => [
                    'enable' => 0,
                    'secret_key' => '',
                ],
            ],
        ]);

        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->expects(self::never())->method('request');

        $request = $this->buildRequest([
            'action' => 'form',
            'controller' => 'Comment',
        ]);

        $validator = new GoogleCaptchaValidator($configurationManager, $requestFactory);
        $validator->setRequest($request);
        $result = $validator->validate('captcha-field-present');

        self::assertFalse($result->hasErrors());
    }

    #[Test]
    public function requestAttributeShortCircuitsRevalidation(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')->willReturn([
            'comments' => [
                'google_recaptcha' => [
                    'enable' => 1,
                    'secret_key' => 'super-secret',
                ],
            ],
        ]);

        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->expects(self::never())->method('request');

        $request = $this->buildRequest([
            'action' => 'form',
            'controller' => 'Comment',
        ])->withAttribute('t3g-blog-recaptcha-verified', true);

        $validator = new GoogleCaptchaValidator($configurationManager, $requestFactory);
        $validator->setRequest($request);
        $result = $validator->validate('captcha-field-present');

        self::assertFalse($result->hasErrors());
    }

    #[Test]
    public function failedVerificationProducesValidationError(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')->willReturn([
            'comments' => [
                'google_recaptcha' => [
                    'enable' => 1,
                    'secret_key' => 'super-secret',
                ],
            ],
        ]);

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn((string) json_encode(['success' => false]));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);

        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->expects(self::once())
            ->method('request')
            ->willReturn($response);

        $request = $this->buildRequest([
            'action' => 'form',
            'controller' => 'Comment',
            'g-recaptcha-response' => 'token',
        ]);

        $validator = new GoogleCaptchaValidator($configurationManager, $requestFactory);
        $validator->setRequest($request);
        $result = $validator->validate('captcha-field-present');

        self::assertTrue($result->hasErrors());
    }

    #[Test]
    public function httpClientExceptionIsTranslatedToValidationError(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')->willReturn([
            'comments' => [
                'google_recaptcha' => [
                    'enable' => 1,
                    'secret_key' => 'super-secret',
                ],
            ],
        ]);

        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->expects(self::once())
            ->method('request')
            ->willThrowException(new \RuntimeException('network timeout'));

        $request = $this->buildRequest([
            'action' => 'form',
            'controller' => 'Comment',
            'g-recaptcha-response' => 'token',
        ]);

        $validator = new GoogleCaptchaValidator($configurationManager, $requestFactory);
        $validator->setRequest($request);
        $result = $validator->validate('captcha-field-present');

        self::assertTrue($result->hasErrors());
    }

    /**
     * @param array<string, mixed> $formValues
     */
    private function buildRequest(array $formValues): ServerRequestInterface
    {
        return (new ServerRequest())
            ->withAttribute('normalizedParams', new NormalizedParams([
                'HTTP_HOST' => 'example.org',
                'REMOTE_ADDR' => '127.0.0.1',
                'REQUEST_URI' => '/',
                'SCRIPT_NAME' => '/index.php',
            ], [], '/var/www/html/index.php', '/var/www/html'))
            ->withParsedBody([
                'tx_blog_commentform' => $formValues,
                'g-recaptcha-response' => $formValues['g-recaptcha-response'] ?? '',
            ]);
    }
}
