import requests
import urllib
import json
import numpy as np

chunk_size = 25
get_diff_url = "http://studio-tadema.net/control_files.php?action=diff&limit="+str(chunk_size)
cnt = 0
while (True):
    file_list = json.loads(urllib.request.urlopen(get_diff_url).read())
    delete_list = [{'rel_path':f['rel_path']} for f in file_list if f['status'] == 3]
    if (len(delete_list) > 0):
        r = requests.post("http://studio-tadema.net/control_files.php?action=delete", \
                        json={'delete':delete_list})
        cnt = len(json.loads(r.text))
    restore_list = [{'rel_path':f['rel_path']} for f in file_list if (f['status'] == 2) and (f['rel_path'] != './control_files.php')]
    if (len(restore_list) > 0):
        r = requests.post("http://studio-tadema.net/control_files.php?action=restore", \
                        json={'restore': restore_list})
        cnt += len(json.loads(r.text)) 
    if (cnt == 0):
        break

#    for files_chunk in np.array_split(np.array(file_list), chunk_size):
#        r = requests.post("http://studio-tadema.net/control_files.php?action=delete_injected", \
#                        json={'delete':[{'rel_path':f['rel_path']} for f in files_chunk if (f['status'] == 3)) ]})

