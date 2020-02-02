import requests
import urllib
import json
import numpy as np

def clear_site(fc_url, depth):
    chunk_size = 25
    get_diff_url = fc_url + "?action=diff&limit="+str(chunk_size)+"&depth="+str(depth)
    while (True):
        cnt = 0
        file_list = json.loads(urllib.request.urlopen(get_diff_url).read())
        print(fc_url + ": diff size " + str(len(file_list)))
        delete_list = [{'rel_path':f['rel_path']} for f in file_list if f['status'] == 3]
        if (len(delete_list) > 0):
            r = requests.post(fc_url + "?action=delete", \
                            json={'delete':delete_list})
            cnt = len(json.loads(r.text))
            print(fc_url + ": deleted " + str(cnt))
        restore_list = [{'rel_path':f['rel_path']} for f in file_list if (f['status'] == 2) and (f['rel_path'] != './control_files.php')]
        if (len(restore_list) > 0):
            r = requests.post(fc_url + "?action=restore", \
                            json={'restore': restore_list})
            cnt += len(json.loads(r.text)) 
            print(r.text)
            print(fc_url + ": restored " + str(cnt))

        if (cnt == 0):
            break
clear_site("https://studio-tadema.net/control_files.php", 0)        
clear_site("https://studio-tadema.net/control_files.php", 10000)        
clear_site("https://rzn438043.ru/control_files.php", 0)        
clear_site("https://rzn438043.ru/control_files.php", 1000)        
clear_site("https://7111953.ru/control_files.php", 0)        
clear_site("https://7111953.ru/control_files.php", 1000)        

#    for files_chunk in np.array_split(np.array(file_list), chunk_size):
#        r = requests.post("http://studio-tadema.net/control_files.php?action=delete_injected", \
#                        json={'delete':[{'rel_path':f['rel_path']} for f in files_chunk if (f['status'] == 3)) ]})

