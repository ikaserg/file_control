import requests
import urllib
import json
import numpy as np

def clear_site(fc_url):
    chunk_size = 25
    get_diff_url = fc_url + "?action=diff&limit="+str(chunk_size)
    cnt = 0
    while (True):
        file_list = json.loads(urllib.request.urlopen(get_diff_url).read())
        print(fc_url + "Diff size " + len(file_list))
        delete_list = [{'rel_path':f['rel_path']} for f in file_list if f['status'] == 3]
        if (len(delete_list) > 0):
            r = requests.post(fc_url + "?action=delete", \
                            json={'delete':delete_list})
            cnt = len(json.loads(r.text))
            print(fc_url + "delete " + str(cnt))
        restore_list = [{'rel_path':f['rel_path']} for f in file_list if (f['status'] == 2) and (f['rel_path'] != './control_files.php')]
        if (len(restore_list) > 0):
            r = requests.post(fc_url + "?action=restore", \
                            json={'restore': restore_list})
            cnt += len(json.loads(r.text)) 
            print(fc_url + "restore " + str(cnt))

        if (cnt == 0):
            break
clear_site("http://studio-tadema.net/control_files.php")        
clear_site("https://rzn438043.ru/control_files.php")        

#    for files_chunk in np.array_split(np.array(file_list), chunk_size):
#        r = requests.post("http://studio-tadema.net/control_files.php?action=delete_injected", \
#                        json={'delete':[{'rel_path':f['rel_path']} for f in files_chunk if (f['status'] == 3)) ]})

