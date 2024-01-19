$script = <<-SCRIPT
# Change directory automatically on ssh login
if ! grep -qF "cd /vagrant" /home/vagrant/.bashrc ;
then echo "cd /vagrant" >> /home/vagrant/.bashrc ; fi
chown vagrant: /home/vagrant/.bashrc
SCRIPT

Vagrant.configure("2") do |config|

    config.vm.provider :libvirt do |v|
        v.cpus = "2"
        v.memory = "2048"
    end

    config.vm.provider :virtualbox do |v|
        v.name = "userli"
        v.cpus = "2"
        v.memory = "2048"
    end

    config.vm.box = "debian/bookworm64"
    config.vm.hostname = "userli"
    config.vm.network :private_network, ip: "192.168.60.99"
    config.vm.synced_folder "./", "/vagrant",
      type: "nfs",
      nfs_udp: false,
      linux__nfs_options: ['rw','no_subtree_check','all_squash','async']
    config.ssh.forward_agent = true

    config.vm.provision "ansible" do |ansible|
        ansible.galaxy_role_file = "requirements.yml"
        ansible.playbook = "ansible/playbook.yml"
        ansible.compatibility_mode = "2.0"
    end

    config.vm.provision "shell", inline: $script
end
