<?php

namespace App\Services;

class UserPref {

    private string $theme;

    public function __construct(
    ) {
        $cookieTheme = self::getFromCookie();
        if (!$cookieTheme) {
            $this->theme = 'light';
            self::setCookie($this);
        } else {
            $this->theme = $cookieTheme->get();
        }
    }

    public function get(): string {
        return $this->theme;
    }

    public function switch(): void {
        $this->theme = $this->theme === 'light' ? 'dark' : 'light';
        self::setCookie($this);
    }

    static public function getFromCookie(): ?UserPref {
        $cookie = $_COOKIE['USER_PREF'] ?? null;
        if (!$cookie) {
            return null;
        }

        $data = base64_decode(urldecode($cookie));
        return unserialize($data);
    }

    static public function setCookie(UserPref $userPref): void {
        $data = urlencode(base64_encode(serialize($userPref)));
        setcookie('USER_PREF', $data, time() + 3600 * 24 * 365);
    }

}