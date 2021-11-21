# Deploying K3s to hetzner in the most ridiculous way possible: Keep it simply stupid.

## WTF is this?

After experimenting with a few managed k8s installations to determine costs and features / benefits, I got tired of my
wallet being raped by ridiculous shenanigans. Thus, I turned to Hetzner, where I can get a 16 core bare-metal, 64 gb of
ram for the same cost as 4 cores, 16gb of ram VM in Azure.

Yeah, so that's nice... I guess that explains why this repo has magically come into existence. But this repo is a way to
deploy K3s in a nice reproducable way. It is remarkably easy to fuck this shit up, and I got tired of figuring out what
I did *that time*. I probably could have used Terraform, but fuck those state files and "premium" features. This isn't
rocket science... ok, it also is a bit more complicated than I would have liked. There's Chef and Puppet and Ansible,
but they're too generic and again, I want this to be simple.

I'm tired of bending over backwards for stupid tools pretending to be smart. So I wrote a stupid tool that is just
incredibly stupid. It does things. It doesn't care if the thing worked or not, you should be reading the logs. If it
doesn't work, I'm not fixing it. I literally don't care. The license is "do whatever you want with it, because I'm
already done with it."

And by done, I mean, my cluster is up and running, I don't care. This tool has no reason to get smarter or do anything
other than set up a production-ish K3s cluster in Hetzner dedicated servers. That's it. Fuck the cloud, I don't want to
hear about the cloud.

I will warn you, this **is not for beginners, noobs, or whatever**. This also isn't for professionals. There's nothing
professional about any of this. It's written in PHP 8.0, and I've cussed at least once in the previous paragraphs.

# Getting started -- The Gist

Go into Robot, set the server to reboot into the rescue environment and reboot the server.
Run `./deploy.php server hostname.example.com`. Once your cluster is commissioned, run `./deploy.php user` and fill out
the information for the user `k8s`. In about 5-10 minutes, the cluster will be up and running with the following shiny
things:

1. Private Docker registry with authentication.
2. Fine-grained authentication to said private repository.
3. Cluster set up to use said private repository.
4. cert-manager for certificate stuff.
5. Nginx ingress.
6. [Kilo](https://github.com/squat/kilo) for secure communication between your laptop and the cluster and other
   clusters.
7. Redis, for shoving key-values into it.
8. [dapr](https://dapr.io) for making useful things.
9. Netdata for seeing if the cluster is on fire yet.
10. Longhorn for storage.
11. Any storage boxes mounted via CIFs/SMB.
12. A working firewall (ask me how I know).
13. Probably other things I forgot I did.

# Getting started, the long way...

That last section made it look easy... it's a bit more involved.

## Configuration

Copy `example.com.conf` to a file that very, very, ridiculously closely resembles your future server's hostname. This
will be the configuration applied in the rescue environment when your machine is booted there. If it doesn't exist, and
it detects the rescue environment it probably doesn't crash but may very well delete all your data... I won't fix you
being lazy. But you **can** be lazy, that's 1000% up to you. But don't file a bug when it breaks your server.

Example: `cp example.com.conf withinboredom.info.conf && nano withinboredom.info.conf`

## Start thinking about the future

Now would also be a good time to edit `registries.yaml` and set the future hostname of the private registry and the
user/pass combination. It's "best practices" not to be leaving plain-text in git histories, but if you've seriously made
it this far, that is the least of your concerns. BUT, if you want to be fancy, check
out [git-crypt](https://www.agwa.name/projects/git-crypt/) or something.

## Registry Storage

You probably got a free storage box with your shiny server. So go get the credentials and
update [BasicServer.php](src/Roles/BasicServer.php) with your storage box credentials. We'll use that 100GB+ to store
our registry data in.

## Configuring networks and stuff

Now for the hard part. Go ahead and get your shiny server a ipv6, set up any vlans, and edit [servers.php](servers.php)
with your configuration. Also, use your imagination (or a password manager) to create a fancy cluster secret.

## Deploy the cluster!!

Now you can deploy the cluster: `./deploy.php cluster`.

## It would be a good time to make sure that worked.

I have no idea how to tell you how to do that. But it should maybe, probably be a working cluster-ish. There's still a
bunch of missing pieces... Now would be a perfect time to peruse the deployments folder and customize some things.
You'll notice an ip address in the registry.yaml file. That's because of a bug in nginx ingress that isn't fixed
yet... :sigh:

## Now we need to make some slight modifications to deploy.sh, unless you're me.

You just need to update the `DOCKER_USER` constant to your actual docker user. Also, go ahead and create a public (yes,
public) repo on docker hub. We need this image to bootstrap the repository. Feel free to create a chicken-and-egg type
problem by changing it to use your private repo later. We don't put secrets in it though. We're not that silly.

## Deploy it all

Now, take your user/password from your registries.yaml file and use it when you do `./deploy.php user`. It'll build and
push the docker container (requires [buildx](https://docs.docker.com/buildx/working-with-buildx/)), then deploy all the
files in the `deployments` directory. Once the cluster stabilizes, you'll need to do some more stuff... :sigh:

## Now update that mysterious ip address

Now you can run `kubectl get services -n ingress` to get the internal ip address of the registry-auth service. You'll
need to replace that with the other random ip address in `registries.yaml`. There's a bug that prevents DNS lookups from
working. It's been "fixed" since April, but uh, yeah. I don't think anyone ever checked to see if it actually was fixed.

Side-rant: I ran into that a lot while working on this. In fact, I ran into a bug that was fixed in 2018, the PR is
still open, waiting for someone to come along and merge it in what appeared to be a pretty well maintained repository. I
think that says a bunch without saying much...

## Anyway, time to redeploy

Now you are almost done. With that fancy ip address, from your server run `curl http://your-ip/cost.php`. It will give
you a cost to use by benchmarking your server. This number goes at the top of `deploy.php` and it's kinda important so
people don't come along and brute-force your credentials.

If your number is different than 11, you'll need to rehash your password after updating the number: `./deploy.php user`.

Now may also be a good time to tune `auth.json`... after making changes, run `./deploy.php user update`

# That's a ton of work...

But you're done. Your idle server is using 6-10% of 16ish 2.5GHz cores doing God Knows What. But you have Kubernetes
running on a server. Yay? You could also spend half that money on decent PHP hosting, but here we are...

# Key Take Away...

...Is not Chinese or Indian food. I'm 999999.99999999% sure that a standard LAMP setup would be far easier to set up. In
fact, I'm sure that it would literally be 3 commands in my terminal to get something up and running with relatively sane
defaults and another few hours to have it ready for production workloads. Is this more scalable? Maaaayyybeee?

I think if you program your app from a shared-nothing concept, anything is scalable. Kubernetes allows you to pretend
you don't need to. And that's worth something. I write a lot of PHP, I'm used to it literally being impossible to share
data between requests, but I've also written a lot of Node.js, C#, and other things where you can write some amazing
things, very quickly, by simply pretending you can share some global state. That works, for a little while.

Anyway, I'm just rambling at 3am at this point. I have no idea if any of this works. The private repo version of this is
much better and I'm still backporting things here.
