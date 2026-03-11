<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Domain\Validator;

use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class GoogleCaptchaValidator extends AbstractValidator
{
    protected $acceptsEmptyValues = false;

    public function isValid(mixed $value): void
    {
        $action = 'form';
        $controller = 'Comment';
        $settings = GeneralUtility::makeInstance(ConfigurationManagerInterface::class)
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'blog');
        $request = $this->request ?? $GLOBALS['TYPO3_REQUEST'];
        $queryData = $request->getQueryParams()['tx_blog_commentform'] ?? [];
        $bodyData = $request->getParsedBody();
        $postData = is_array($bodyData) ? ($bodyData['tx_blog_commentform'] ?? []) : [];
        $requestData = array_merge($queryData, $postData);

        if (
            ($GLOBALS['google_recaptcha'] ?? null) === null
            && ($requestData['action'] ?? null) === $action
            && ($requestData['controller'] ?? null) === $controller
            && (int)($settings['comments']['google_recaptcha']['enable'] ?? 0) === 1
        ) {
            $additionalOptions = [
                'headers' => ['Content-type' => 'application/x-www-form-urlencoded'],
                'query' => [
                    'secret' => $settings['comments']['google_recaptcha']['secret_key'],
                    'response' => $bodyData['g-recaptcha-response'] ?? '',
                    'remoteip' => GeneralUtility::getIndpEnv('REMOTE_ADDR')
                ]
            ];
            $response = GeneralUtility::makeInstance(RequestFactory::class)
                ->request('https://www.google.com/recaptcha/api/siteverify', 'POST', $additionalOptions);
            if ($response->getStatusCode() === 200) {
                $result = json_decode($response->getBody()->getContents());
                if (!$result->success) {
                    $this->addError('The re-captcha failed', 1501341100);
                } else {
                    $GLOBALS['google_recaptcha'] = true;
                }
            }
        }
    }
}
