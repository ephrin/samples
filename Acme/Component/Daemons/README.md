###Daemon scripts helpers lib

For usage under utilities such like supervisor.
 
When we have amount of jobs to be done and do not know when PHP will fail to execute it.
For example: loop with periodic job monitoring, daemon client, server or something
That script wraps your loop with own callback (client implementation) 
and could be configured to make soft exit by specified conditions match. 
 
  There client implementation for:
   - Gearman Job Server Workers.
   - Standalone daemon worker
  Also present:
   - possibility to correctly exit with defined status code
   - exit condition triggers such like:  
        - callback exception, 
        - return value, 
        - error count,
        - iteration count
        - seconds run count
        - failures count
        - memory usage trigger
        - memory usage diff trigger
        - peak memory usage trigger
        - memory leak

####Suppression 
There also present suppression mechanism (only mongodb driver implementation).

It provides possibility to suppress softly daemon script by internal status source monitoring.
  You know, when we need to restart all, but there could be some long cycle runtime executions, 
  and to stop it after current iteration finish we need to do it from execution side, not by SIGTERM or whatever else.