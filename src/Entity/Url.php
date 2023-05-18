<?php

namespace App\Entity;

use App\Repository\UrlRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UrlRepository::class)]
class Url
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 2048, unique: true)]
    private ?string $url = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $urlHash = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $this->normalizeUrl($url);

        return $this;
    }

    public function normalizeUrl(string $url): string
    {
        $parts = parse_url($url);

        // lowercase scheme and host
        $parts['scheme'] = strtolower($parts['scheme'] ?? '');
        $parts['host'] = strtolower($parts['host'] ?? '');
    
        // remove default ports
        if (($parts['scheme'] === 'http' && ($parts['port'] ?? null) === 80) ||
            ($parts['scheme'] === 'https' && ($parts['port'] ?? null) === 443)) {
            unset($parts['port']);
        }
        
        unset($parts['scheme']);
        return $this->unparseUrl($parts);
    }
    
    private function unparseUrl(array $parsedUrl): string
    {
        $get = function($key) use ($parsedUrl) { return $parsedUrl[$key] ?? null; };
    
        $pass      = $get('pass');
        $user      = $get('user');
        $userinfo  = $pass !== null ? "$user:$pass" : $user;
        $port      = $get('port');
        $host      = $get('host');
        $authority = $userinfo !== null ? "$userinfo@$host" : $host;
        $authority = $port ? "$authority:$port" : $authority;
    
        return (isset($parsedUrl['host']) ? "//" : '') .
            $authority .
            $get('path') .
            (isset($parsedUrl['query']) ? "?{$parsedUrl['query']}" : '') .
            (isset($parsedUrl['fragment']) ? "#{$parsedUrl['fragment']}" : '');
    }

    public function getUrlHash(): ?string
    {
        return $this->urlHash;
    }

    public function setUrlHash(string $urlHash): self
    {
        $this->urlHash = $urlHash;

        return $this;
    }
}
