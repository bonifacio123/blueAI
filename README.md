# blueAI

A tool that triggers BlueIris security software using the Deepstack AI service to determine if an event is valid or not. This code was inspired by Gentlepumplins AI Tool and neile's docker version. I preferred something simpler that natively ran on the Raspberry Pi. This was created in a few hours to fulfill a need and share in case anyone is interested and wants to make it better.
There are no external triggers when AI detects a match on a object other than trigger the BlueIris HD camera. You can extend trigger functionality by coding blueAI_triggered.phpThis code is inspired by Gentlepumplins AI Tool and neile's docker version. I preferred something simpler that natively ran on the Raspberry Pi. This was created in a few hours to fulfill a need and share in case anyone is interested and wants to make it better. There are no external triggers when AI detects a match on a object other than trigger the BlueIris HD camera. You can extend trigger functionality by coding blueAI_triggered.php

### Installation

1. Configure your apache to listen on port 81
2. Install Deepstack as per their instructions. Bump your OS swap space to 1G if you're installing on a Pi 3 otherwise the compile never finishes. Also, sudo apt install gfortran before installing deepstack or it'll complain/fail.
3. Copy the blueAI files to /home/pi/blueAI/
4. Modify blueAI_settings.json for your setup and then:
   ```sudo cp /home/pi/blueAI/blueAI_settings.json /etc/.```
5. Add blueAI_daemon.php to cron:
   ```* * * * * php /home/pi/blueAI/blueAI_daemon.php```
6. Copy to apache htdocs:
   ```ln -s /home/pi/blueAI/blueAI_ui.php /var/www/html/bluai_ui.php
   ln -s /home/pi/blueAI/blueAI_image.php /var/www/html/bluai_image.php```
7. blueAI looks for BlueIris files in /mnt/aiinput. I have a windows share auto mounted to /mnt/aiinput
    ```sudo mount -t cifs -o username=yourusername,password=yourpassword //WindowsPC/share1 /mnt/aiinput```
    >note: I had to set my Pi to wait for network during boot otherwise mount will run before ethernet is up and won't auto mount
8. If you want to use the watchdog to alert you via email if Deepstack stops processing then add this to cron:
    ```0,30 * * * * php /home/pi/blueAI/blueAI_watchdog.php > /tmp/blueAI_watchdog.log```
9. Secure files as necesary.

### Installation

1. Connect using your browser to http://[your apache ip]:81/blueAI_ui.php

### License

Copyright &copy; 2020 John Navarro
Distributed under the [MIT License](http://www.opensource.org/licenses/mit-license.php).
