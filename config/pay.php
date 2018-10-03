<?php

return [
	'alipay' => [
		'app_id' => '2016092100558961',
		'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA3+HEAax+zkgyckLXefpX3ihJetVu8c0D6VOAHTmHIXHHdEETzOoLteRxv++uzAJPgSz3OswG4FtLAn+nykG5vfFI1m7/WBw7wVoWMB+Sz7qDfj/SaQVZtQXX53uqxO5bxH9DtjmvV7DAlzfS0A/ncEBw5Ve342GXKgvabjA0MfrjyXKWN1tsb6JoQoKHmyXs6Ry/p/fpq2S+O5BUU37JY808SKb45SK4v3z0KAlNdEn0CQ6+Q0Ul20ngp1pU+b6SPGqlqDBTYqk0aRhvNTMliw0rN+Arr2iqlhsgHBNeCU8nc0Lv4ynCHNRre8IRLGEBN0wuge5e+f8nk3wFlCFHuQIDAQAB',
		'private_key' => 'MIIEpAIBAAKCAQEAr0Q0U49Mr9OApCI/ypwJpYKA2DRwI26+AklcuxFag43LtqfZX0wnnxJtjJbGZGihaAZ44x/LVTEgZpEg8YSJnYXHktqEKpmQcZgCi06u5N7AwP4Fk5QiHCbVwc0h/1jEsPr0OF7WT1PGZDSSykBK2UdhbKAZVJb85NJ/SIAFEWhzDarF7YiDX6Ua/9AvjFcLcJjeD/3kkclDoCNa86sC9Dws5M30A4Uj8DSLy/28zqGxELagxajc94bMJbsqlM6mLAYQTDw+5YZcymmat/QjkTYA+53CpoHc6+d+q6Bb9noICP45vnYEV80YFlGwK52zNrLzUKF77B7Cj5xmDqFlhwIDAQABAoIBAQCpjSmxzGc7kThZkGh18Q3D98P9ZH03SeK3A5GZxufxbDMuuKIo9ts9n+4qm5HBgRbkM56u2gsylxvpHWzpOHaI1OqEvrAtJfH+cjfD3JKFBpkiGpv6cMuuwEL6ASBqjc16CMxJ3DUm+LxsJA/9aMT//XXaR+c5VffAxYIs+OvuManp8Q0/tstUwm5C7SBD3cx9VviJq18pC8pyanko8jX3HnUZC/P9LC+uwyVAhdbXr8S2OfMw2ZQ6lxAq+LZk1IRc/GbCqkPGUzRdWEMvCBu+BcrhUzeRksjDcQd8HKMw9EXTKgXByY1JbjGnxHFt7/jsjse8r2+sa8HUYFiVyXjBAoGBAOd0IcuHNMlOvp3yU3plFJPckMIT82DfIQ5JFK3s5QTzzFVhUV8J3GPBH0TZ9eXw271iRYH657LfnnWmM042pI1xzsSouRr2It8WoC3kn0Q8T2THCzrNA1c8kMWclKGmvDOPAbUQ7oOvaxiz++W8JY1NbDIGWWbgMzKWdK6xM9h9AoGBAMHanQJX1syoeWwB6Lcm8cXuqNMTDqVW7/ieM1uDOP6kc+1ieAoJQsBE2APF5Hhj9wCLyU0ioZ3JCwUefUrSN39Y8vW3iS/zCwCwJnSENWXx83aLN15SM0/PmiCJS6An6g64hnrf7K3F1DU9vmHtFuOK/E/cZAGufItrA4yixhlTAoGBAIRyiXBNo+Ba5mu+IxUUSM1AV7on7osNxH3HRkUtHfVSiiiFsynem7ad6gXdcICv7x8V4E68ROCwZJ5QiGWGkW27paYWIy8RkOz2ppz8ikNi+8/gs0Vn0jSWnQXoT4mdv28Fs+VolgTXWkLdpBVYMGkG2BZcWcasH8AgJw9cqh2FAoGAKjcxehEm9eq6horj4v7YKAKRQWYlClykZcAN7x/kiY/GryuCeK0LnFNht3ChEJa0c6n9bI7eIz5k5/h70I93BoSYZGpTtd13x+6UcUtZVZobKvWmWSQNiJPtPKipj3chwZLttlSNdkperDmF/E1lbqgVyk50eOlGthXX8AiCm4ECgYBOn6ICCH2NE2LL+xcbauK8ejVXXZmkWHxyE3WvTXw2ri9vEijgKPZZjkQ0v1NhCzp4zzcBmspxjFn/2SKvjo5nhbi4kxxcJLrDPh5+XCIzzmSzH+ecgQfJVNviUNB1xwGVMFfT3dbpIfeHLeoOR7Oceo2EbXPZ9qBoEMFNJcuNLQ==',
		'log' => [
			'file' => storage_path('logs/alipay.log'),
		],
	],

	'wechat' => [
		'app_id' => '',
		'mch_id' => '',
		'key' => '',
		'cert_client' => '',
		'cert_key' => '',
		'log' => [
			'file' => storage_path('logs/wechat_pay.log'),
		],
	],
];