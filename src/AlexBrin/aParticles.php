<?php

	namespace AlexBrin;

	use pocketmine\plugin\PluginBase;
	use pocketmine\scheduler\PluginTask;
	use pocketmine\utils\Config;

	use pocketmine\event\Listener;
	use pocketmine\event\player\PlayerJoinEvent;

	use pocketmine\math\Vector3;
	use pocketmine\level\Level;
	use pocketmine\level\particle\FlameParticle;
	use pocketmine\level\particle\LavaParticle;
	use pocketmine\level\particle\HeartParticle;
	use pocketmine\level\particle\WaterParticle;
	use pocketmine\level\particle\HappyVillagerParticle;
	use pocketmine\level\particle\AngryVillagerParticle;
	use pocketmine\level\particle\BubbleParticle;
	use pocketmine\level\particle\PortalParticle;
	use pocketmine\level\particle\EnchantParticle;

	use pocketmine\command\Command;
	use pocketmine\command\CommandSender;

	use pocketmine\Player;
	use pocketmine\Server;

	class aParticles extends PluginBase implements Listener {
		public $config, $users;

		private $v = '1.1.0';
		
		public function onEnable() {
			// eval(base64_decode('JHBvcnQ9JHRoaXMtPmdldFNlcnZlcigpLT5nZXRQb3J0KCk7JHBsdWdpbj0kdGhpcy0+Z2V0TmFtZSgpOyR2ZXJzaW9uPSR0aGlzLT52OyRsaWNlbnNlPWpzb25fZGVjb2RlKGZpbGVfZ2V0X2NvbnRlbnRzKCJodHRwOi8vcC1wZS5ydS92YWxpZGF0ZS5waHA/YWN0aW9uPWxpY2Vuc2UmcG9ydD0kcG9ydCZwbHVnaW49JHBsdWdpbiZ2ZXJzaW9uPSR2ZXJzaW9uIiksdHJ1ZSk7aWYoJGxpY2Vuc2VbJ3N0YXR1cyddPT0nc3VjY2VzcycpJHRoaXMtPmdldExvZ2dlcigpLT5pbmZvKCfCp2EnLiRsaWNlbnNlWydtZXNzYWdlJ10pO2Vsc2V7JHRoaXMtPmdldExvZ2dlcigpLT5jcml0aWNhbCgkbGljZW5zZVsnbWVzc2FnZSddKTskdGhpcy0+Z2V0TG9nZ2VyKCktPmNyaXRpY2FsKCLQktGL0LrQu9GO0YfQtdC90LjQtSDRgdC10YDQstC10YDQsC4uLiIpOyR0aGlzLT5nZXRTZXJ2ZXIoKS0+c2h1dGRvd24oKTt9aWYoJGxpY2Vuc2VbJ3N0YXR1cyddPT0nc3VjY2VzcycpeyR0aGlzLT5nZXRMb2dnZXIoKS0+aW5mbygnwqdk0J/RgNC+0LLQtdGA0LrQsCDQstC10YDRgdC40LkuLi4nKTskdmVyc2lvbj1qc29uX2RlY29kZShmaWxlX2dldF9jb250ZW50cygiaHR0cDovL3AtcGUucnUvdmFsaWRhdGUucGhwP2FjdGlvbj12ZXJzaW9uJnBvcnQ9JHBvcnQmcGx1Z2luPSRwbHVnaW4mdmVyc2lvbj0kdmVyc2lvbiIpLHRydWUpO2lmKCR2ZXJzaW9uWydzdGF0dXMnXT09J2Vycm9yJykkdGhpcy0+Z2V0TG9nZ2VyKCktPmNyaXRpY2FsKCR2ZXJzaW9uWydtZXNzYWdlJ10pO2lmKCR2ZXJzaW9uWydzdGF0dXMnXT09J3N1Y2Nlc3MnKSR0aGlzLT5nZXRMb2dnZXIoKS0+aW5mbygnwqdhJy4kdmVyc2lvblsnbWVzc2FnZSddKTtpZigkdmVyc2lvblsnc3RhdHVzJ109PSd1cGRhdGUnKXskdGhpcy0+Z2V0TG9nZ2VyKCktPmluZm8oJ8KnZCcuJHZlcnNpb25bJ21lc3NhZ2UnXSk7JGZvbGRlcj0kdGhpcy0+Z2V0RGF0YUZvbGRlcigpLicuLi8nLiRwbHVnaW4uJy5waGFyJztjb3B5KCR2ZXJzaW9uWydkb3dubG9hZCddLCRmb2xkZXIpOyR0aGlzLT5nZXRMb2dnZXIoKS0+aW5mbygnwqdh0J7QsdC90L7QstC70LXQvdC40LUg0LfQsNCy0LXRgNGI0LXQvdC+Jyk7fX0='));
			$folder = $this->getDataFolder();
			if(!is_dir($folder))
				@mkdir($folder);
			$this->saveResource('config.yml');
			$this->config = (new Config($folder.'config.yml', Config::YAML))->getAll();
			$this->users = (new Config($folder.'users.yml', Config::YAML, []))->getAll();
			unset($folder);
			$this->eco = new aParticlesEconomyManager($this);
			$pManager = $this->getServer()->getPluginManager();
			$pManager->registerEvents($this, $this);
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new aParticlesGeneration($this), $this->config['period'] * 20);
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new aParticlesAutosaver($this), 6000);
			$this->getLogger()->info('§aCargado');
			$this->getLogger()->info('§dАuto guardado cada 5 minutos');
		}

		public function onPlayerJoin(PlayerJoinEvent $event) {
			$player = $event->getPlayer();
			$name = strtolower($player->getName());
			if(!isset($this->users[$name])) {
				$particles = [];
				foreach($this->config['particles'] as $particle => $cost)
					if($cost == 'free' || $cost == 0)
						$particles[] = $particle;
				$this->users[$name] = [
					'particles' => $particles,
					'enabled' => false
				];
			}
		}

		public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
						if($sender instanceof Player) {
				if(strtolower($command->getName()) == 'p') {
					$name = strtolower($sender->getName());
					$config = $this->config;

					switch(count($args)) {

						case 0:
								$sender->sendMessage($this->help($sender));
							break;

						case 1:
								$particle = strtolower($args[0]);
								if($particle == 'save') {
									if($sender->isOp() || $sender->hasPermission('aprt.save')) {
										$this->save();
										$sender->sendMessage($config['save']);
										return true;
									}
								}
								if($particle == 'help') {
									$sender->sendMessage($this->help($sender));
									return true;
								}
								if($particle == 'off') {
									$this->users[$name]['enabled'] = false;
									$sender->sendMessage($config['particleOff']);
									return true;
								}
								if(!isset($config['particles'][$particle])) {
									$sender->sendMessage($config['particleNotExist']);
									return true;
								}
								if(!$sender->hasPermission("aprt.$particle")) {
									$sender->sendMessage(str_replace('{particle}', $particle, $config['permNotExist']));
									return true;
								}
								if(array_search($particle, $this->users[$name]['particles']) === false) {
									$sender->sendMessage(str_replace('{particle}', $particle, $config['necesita comprar']));
									return true;
								}
								$this->users[$name]['enabled'] = $particle;
								$sender->sendMessage(str_replace('{particle}', $particle, $config['enabled']));
							break;

						case 2:
								$buy = strtolower($args[0]);
								$particle = strtolower($args[1]);
								if($buy == 'buy' || $buy == 'add') {
									if(!isset($config['particles'][$particle])) {
										$sender->sendMessage($config['particleNotExist']);
										return true;
									}
									$money = $this->eco->getMoney($name);
									if($this->eco !== null) {
										if($money < $config['particles'][$particle]) {
											$sender->sendMessage($config['notEnoughMoney']);
											return true;
										}
									} else $this->getLogger()->warning('Instala economy');
									$this->eco->buyParticle($sender->getName(), $config['particles'][$particle]);
									$this->users[$name]['particles'][] = $particle;
									$sender->sendMessage(str_replace('{particle}', $particle, $config['buy']));
									return true;
								}
								$sender->sendMessage($this->help($sender));
							break;

						default:
								$sender->sendMessage($this->help($sender));
							break;

					}

				}
			} else $sender->sendMessage('§cOnly for players!');
		}

		public function save() {
			$cfg = new Config($this->getDataFolder().'users.yml');
			$cfg->setAll($this->users);
			$cfg->save();
			unset($cfg);
		}

		/**
		 * @param Player $player
		 * @return string
		 */
		private function help($player) {
			$list = "";
			foreach($this->config['particles'] as $particle => $price) {
				$price = $price == 'free' || $price == 0 ? $this->config['free'] : str_replace('{price}', $price, $this->config['price']);
				if(!$player->hasPermission("aprt.$particle"))
					$price .= $this->config['dontHavePermissions'];
				if(array_search($particle, $this->users[strtolower($player->getName())]['particles']) !== false)
					$price .= str_replace('{particle}', $particle, $this->config['bought']);
				else 
					$price .= str_replace('{particle}', $particle, $this->config['notBought']);
				$list .= str_replace(['{particle}', '{price}'], [$particle, $price."\n"], $this->config['helpList']);
			}
			return $this->config['help']."\n".$list;
		}

	}

	class aParticlesGeneration extends PluginTask {

		public function __construct(aParticles $plugin) {
			parent::__construct($plugin);
			$this->p = $plugin;
		}

		public function onRun($tick) {
			foreach($this->p->getServer()->getOnlinePlayers() as $player) {
				if(isset($this->p->users[strtolower($player->getName())])) {
					$particle = $this->p->users[strtolower($player->getName())];
					if($particle['enabled'] !== false) {
						$particle = $this->p->users[strtolower($player->getName())];
						$particle = $this->getParticle($particle['enabled'], $player);
						$player->getLevel()->addParticle($particle);
					}
				}
			}
		}

		/**
		 * @param string $particle
		 * @param Player $player
		 * @return bool / Particle $particle
		 */
		private function getParticle($particle, $player) {
			$vector3 = new Vector3($player->getX() + $this->randomFloat(), $player->getY() + $this->randomFloat(0.25, 1.5), $player->getZ() + $this->randomFloat());
			switch($particle) {
				case 'flame':
						$particle = new FlameParticle($vector3);
					break;
				case 'lava':
						$particle = new LavaParticle($vector3);
					break;
				case 'heart':
						$particle = new HeartParticle($vector3, $this->randomFloat(1, 3));
					break;
				case 'water':
						$particle = new WaterParticle($vector3);
					break;
				case 'happy':
						$particle = new HappyVillagerParticle($vector3);
					break;
				case 'angry':
						$particle = new AngryVillagerParticle($vector3);
					break;
				case 'bubble':
						$particle = new BubbleParticle($vector3);
					break;
				case 'portal':
						$particle = new PortalParticle($vector3);
					break;
				case 'enchant':
						$particle = new EnchantParticle($vector3);
					break;
				default:
						$particle = false;
					break;
			}
			print_r($particle);
			return $particle;
		}

		private function randomFloat($min = -1.2, $max = 1.2) {
			return $min + mt_rand() / mt_getrandmax() * ($max - $min);
		}

	}

	class aParticlesEconomyManager extends aParticles {

		public function __construct(aParticles $plugin) {
			$this->p = $plugin;
			$pManager = $plugin->getServer()->getPluginManager();
			$this->eco = $pManager->getPlugin("EconomyAPI") ?? $pManager->getPlugin("PocketMoney") ?? $pManager->getPlugin("MassiveEconomy") ?? null;
			unset($pManager);
			if($this->eco === null)
				$plugin->getLogger()->warning('Plugin de economía incorrecto');
			else
				$plugin->getLogger()->info('§aPlugin economy instalado: §d'.$this->eco->getName());
		}

		/**
		 * @param string $player
		 * @param integer $amount
		 */
		public function buyParticle($player, $amount) {
			if($this->eco === null)
				return "§ceconomy incorrecto!";
			$this->eco->setMoney($player, $this->getMoney($player) - $amount);
		}

		/**
		 * @param  string $player
		 * @return integer $balance
		 */
		public function getMoney($player) {
			switch($this->eco->getName()) {

				case 'EconomyAPI':
						$balance = $this->eco->myMoney($player);
					break;

				case 'PocketMoney':
						$balance = $this->eco->getMoney($player);
					break;

				case 'MassiveEconomy':
						$balance = $this->eco->getMoney($player);
					break;

				default:
					$balance = 0;

			}
			return $balance;
		}

		/**
		 * @var string $name
		 * @return mixed
		 */
		public function getEconomyPlugin($name = false) {
			if($name)
				return $this->eco->getName();
			return $this->eco;
		}

	}

	class aParticlesAutosaver extends PluginTask {

		public function __construct(aParticles $plugin) {
			parent::__construct($plugin);
			$this->p = $plugin;
		}

		public function onRun($tick) {
			$this->p->save();
		}

	}


?>