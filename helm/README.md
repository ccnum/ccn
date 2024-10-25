# Helm chart

**Install Helm**

```bash
helm install my-release-1 ./my-chart --namespace my-namespace -f values.yaml
helm install my-release-2 ./my-chart --namespace my-namespace
```

**Uninstall Helm**

```bash
helm uninstall my-release-1 --namespace my-namespace
```

**Upgrade Helm**

```bash
helm upgrade my-release-1 ./my-chart --namespace my-namespace -f values.yaml
```



helm install fictions . --namespace ccn-test -f custom.yaml
