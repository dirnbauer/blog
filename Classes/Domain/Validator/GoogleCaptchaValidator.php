<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Domain\Validator;

use Psr\Http\Message\ServerRequestInterface;
use T3G\AgencyPack\Blog\Utility\RequestUtility;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class GoogleCaptchaValidator extends AbstractValidator
{
    private const VERIFY_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';
    private const VERIFY_TIMEOUT_SECONDS = 5.0;
    private const REQUEST_ATTRIBUTE = 't3g-blog-recaptcha-verified';

    protected $acceptsEmptyValues = false;

    public function __construct(
        private readonly ConfigurationManagerInterface $configurationManager,
        private readonly RequestFactory $requestFactory,
    ) {
    }

    public function isValid(mixed $value): void
    {
        $action = 'form';
        $controller = 'Comment';
        $settings = $this->configurationManager
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'blog');
        $request = $this->resolveRequest();
        $queryData = $request->getQueryParams()['tx_blog_commentform'] ?? [];
        if (!is_array($queryData)) {
            $queryData = [];
        }
        $bodyData = $request->getParsedBody();
        $postData = is_array($bodyData) ? ($bodyData['tx_blog_commentform'] ?? []) : [];
        if (!is_array($postData)) {
            $postData = [];
        }
        $requestData = array_merge($queryData, $postData);

        if (
            $request->getAttribute(self::REQUEST_ATTRIBUTE) !== true
            && ($requestData['action'] ?? null) === $action
            && ($requestData['controller'] ?? null) === $controller
            && (int)($settings['comments']['google_recaptcha']['enable'] ?? 0) === 1
        ) {
            $captchaResponse = is_array($bodyData) ? (string)($bodyData['g-recaptcha-response'] ?? '') : '';
            $additionalOptions = [
                'headers' => ['Content-type' => 'application/x-www-form-urlencoded'],
                'timeout' => self::VERIFY_TIMEOUT_SECONDS,
                'query' => [
                    'secret' => $settings['comments']['google_recaptcha']['secret_key'],
                    'response' => $captchaResponse,
                    'remoteip' => RequestUtility::getNormalizedParams($request)->getRemoteAddress(),
                ],
            ];
            try {
                $response = $this->requestFactory
                    ->request(self::VERIFY_ENDPOINT, 'POST', $additionalOptions);
            } catch (\Throwable $exception) {
                $this->addError('The re-captcha failed', 1501341100);
                return;
            }

            if ($response->getStatusCode() !== 200) {
                $this->addError('The re-captcha failed', 1501341100);
                return;
            }

            $result = json_decode($response->getBody()->getContents(), true);
            if (!is_array($result) || ($result['success'] ?? false) !== true) {
                $this->addError('The re-captcha failed', 1501341100);
            } else {
                $request = $request->withAttribute(self::REQUEST_ATTRIBUTE, true);
                $GLOBALS['TYPO3_REQUEST'] = $request;
            }
        }
    }

    private function resolveRequest(): ServerRequestInterface
    {
        if ($this->request instanceof ServerRequestInterface) {
            return $this->request;
        }

        return RequestUtility::getGlobalRequest();
    }
}
