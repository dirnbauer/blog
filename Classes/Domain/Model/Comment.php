<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Comment extends AbstractEntity
{
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 10;
    public const STATUS_DECLINED = 50;
    public const STATUS_DELETED = 90;

    /**
     * The name of the comment author.
     *
     * @var string
     */
    protected $name;

    /**
     * The email of the comment author.
     *
     * @var string
     */
    protected $email;

    /**
     * The url of the comment author.
     *
     * @var string
     */
    protected $url;

    /**
     * The comment text.
     *
     * @var string
     */
    protected $comment;

    /**
     * Flag to determine if record is hidden.
     *
     * @var int
     */
    protected $hidden;

    /**
     * The post related to this comment.
     *
     * @var \T3G\AgencyPack\Blog\Domain\Model\Post
     */
    protected $post;

    /**
     * The honeypot field, field is not stored in database.
     *
     * @var string
     */
    protected $hp = '';

    /**
     * @var int
     */
    protected $postLanguageId;

    /**
     * The blog post creation date.
     *
     * @var \DateTime
     */
    protected $crdate;

    /**
     * @var int
     */
    protected $status;

    /**
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     *
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     *
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     *
     */
    public function setComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     */
    public function getHidden(): ?int
    {
        return $this->hidden;
    }

    /**
     *
     */
    public function setHidden(int $hidden): self
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     *
     */
    public function setUrl(string $url): self
    {
        $url = trim($url);
        if ($url === '') {
            $this->url = '';
            return $this;
        }

        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        if ($scheme === '') {
            $url = 'https://' . ltrim($url, '/');
            $scheme = 'https';
        }

        if (!in_array($scheme, ['http', 'https'], true)) {
            $this->url = '';
            return $this;
        }

        $this->url = $url;
        return $this;
    }

    /**
     */
    public function getPost(): ?Post
    {
        return $this->post;
    }

    /**
     *
     */
    public function setPost(Post $post): self
    {
        $this->post = $post;
        return $this;
    }

    /**
     */
    public function getCrdate(): ?\DateTime
    {
        return $this->crdate;
    }

    /**
     *
     */
    public function setCrdate(\DateTime $crdate): self
    {
        $this->crdate = $crdate;
        return $this;
    }

    /**
     */
    public function getPostLanguageId(): ?int
    {
        return $this->postLanguageId;
    }

    /**
     * @param int $postLanguageId
     */
    public function setPostLanguageId($postLanguageId): self
    {
        $this->postLanguageId = $postLanguageId;
        return $this;
    }

    /**
     */
    public function getHp(): ?string
    {
        return $this->hp;
    }

    /**
     * @param string $hp
     */
    public function setHp($hp): self
    {
        $this->hp = $hp;
        return $this;
    }

    /**
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status): self
    {
        $this->status = $status;
        return $this;
    }
}
